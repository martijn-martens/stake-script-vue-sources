<?php

namespace App\Services;

use App\Events\GamePlayed;
use App\Events\MultiplayerGameAction;
use App\Facades\AccountTransaction;
use App\Models\Account;
use App\Models\Game;
use App\Models\MultiplayerGame;
use App\Models\ProvablyFairGame;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

abstract class MultiplayerGameService
{
    private $user;
    private $multiplayerGame;
    private $game;

    /**
     * @var $gameableClass - should be defined in every child class
     */
    protected $gameableClass;

    /**
     * Make a game secret (reels positions, shuffled cards deck) - before applying client seed
     *
     * @return string
     */
    abstract public function makeSecret(): string;

    /**
     * Get action data to be broadcasted
     *
     * @param string $action
     * @return array
     */
    abstract public function getGameActionData(string $action, array $request = []): array;

    /**
     * Complete game action
     *
     * @param $request
     * @return MultiplayerGameService
     */
    abstract protected function play(array $request): MultiplayerGameService;

    /**
     * Complete game action
     *
     * @param $params
     * @return MultiplayerGameService
     */
    abstract protected function beforeComplete(): MultiplayerGameService;

    /**
     * Calculate win for each user when the game is finished
     *
     * @param Game $game
     * @param Model $gameable
     */
    abstract protected function calculateResult(Game $game, Model $gameable): Game;

    /**
     * Create a gameable model instance
     *
     * @return MultiplayerGameService
     */
    abstract public function createGameable(): Model;

    abstract public function getGameDuration(): int;

    abstract public static function createRandomGame(User $user, MultiplayerGame $multiplayerGame): void;

    public function __construct(User $user)
    {
        if (!$this->gameableClass) {
            throw new Exception('Gameable model should be explicitly set in the child class before calling MultiplayerGameService constructor.');
        }

        // check if a specific user is passed in, otherwise get the user from the request
        $this->user = $user->getKey() ? $user : auth()->user();
    }

    /**
     * Create a new game
     *
     * @return MultiplayerGameService
     */
    public function init(int $delay = 0): MultiplayerGameService
    {
        $multiplayerGame = MultiplayerGame::where('end_time', '>', Carbon::now())
            ->with('provablyFairGame', 'gameable')
            ->orderBy('id', 'desc')
            ->first();

        if (!$multiplayerGame) {
            $provablyFairGame = $this->createProvablyFairGame();
            $gameable = $this->createGameable();

            $multiplayerGame = new MultiplayerGame();
            $multiplayerGame->start_time = Carbon::now()->addSeconds($delay);
            $multiplayerGame->end_time = $multiplayerGame->start_time->addSeconds($this->getGameDuration());
            $multiplayerGame->provablyFairGame()->associate($provablyFairGame);
            $multiplayerGame->gameable()->associate($gameable);
            $multiplayerGame->save();

            $multiplayerGame->setRelation('provablyFairGame', $provablyFairGame);
            $multiplayerGame->setRelation('gameable', $gameable);
        }

        $this->multiplayerGame = $multiplayerGame;

        return $this;
    }

    public function load(MultiplayerGame $multiplayerGame): MultiplayerGameService
    {
        $this->multiplayerGame = $multiplayerGame->loadMissing('provablyFairGame', 'gameable');

        return $this;
    }

    /**
     * @return MultiplayerGame
     */
    public function getMultiplayerGame(): MultiplayerGame
    {
        return $this->multiplayerGame;
    }

    public function setMultiplayerGame(MultiplayerGame $multiplayerGame): MultiplayerGameService
    {
        $this->multiplayerGame = $multiplayerGame;

        return $this;
    }

    /**
     * Create ProvablyFairGame model instance
     *
     * @return MultiplayerGameService
     * @throws Exception
     */
    public function createProvablyFairGame(): ProvablyFairGame
    {
        return tap(new ProvablyFairGame(), function ($provablyFairGame) {
            $provablyFairGame->secret = $this->makeSecret();
            $provablyFairGame->client_seed = random_int(10000000, 99999999);
            $provablyFairGame->gameable_type = $this->gameableClass;
            $provablyFairGame->save();
        });
    }

    public function getProvablyFairGame(): ?ProvablyFairGame
    {
        return optional($this->getMultiplayerGame())->provablyFairGame;
    }

    protected function getBet(array $request): int
    {
        return $request['bet'] ?? 0;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(Game $game): MultiplayerGameService
    {
        $this->game = $game;

        return $this;
    }

    public function loadGame(): ?Game
    {
        $game = $this->getGameable()->games()->where('games.account_id', $this->getUserAccount()->id)->first();

        return optional($game)->setRelation('account', $this->getUserAccount());
    }

    public function createGame(): Game
    {
        $game = new Game();
        $game->account()->associate($this->getUserAccount());
        $game->provablyFairGame()->associate($this->getProvablyFairGame());
        $game->gameable()->associate($this->getGameable());
        $game->bet = 0;
        $game->win = 0;
        $game->is_in_progress = TRUE;
        $game->save();

        return $game;
    }

    public function getGameable(): ?Model
    {
        return optional($this->getMultiplayerGame())->gameable;
    }

    public function setGameable(Model $gameable): MultiplayerGameService
    {
        $this->gameable = $gameable;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserAccount(): Account
    {
        return $this->getUser()->loadMissing('account')->account;
    }

    protected function createNextMultiplayerGame(): MultiplayerGameService
    {
        $childGameService = get_called_class();
        $this->getMultiplayerGame()->next = (new $childGameService($this->getUser()))
            ->init(config('multiplayer-roulette.interval'))
            ->getMultiplayerGame();

        return $this;
    }

    /**
     * Run a game action
     *
     * @param string $action
     * @param array $request
     * @return MultiplayerGameService
     */
    public function action(string $action, array $request = []): MultiplayerGameService
    {
        $game = $this->loadGame() ?: $this->createGame();
        $this->setGame($game);

        // if game is not yet completed
        if ($game->is_in_progress) {
            // perform game action
            $this->$action($request);

            // make changes in a single DB transaction
            DB::transaction(function () use ($action, $game, $request) {
                $gameable = $this->getGameable();
                $bet = $this->getBet($request);

                $game->bet += $bet;

                // make account transaction if necessary
                // important to make the transaction before the game model is saved
                AccountTransaction::create(
                    $this->getUserAccount(),
                    $game,
                    -$bet,
                    FALSE
                );

                // save current user game only
                $game->save();

                // save gameable
                $gameable->save();
            });

            // broadcast action event
            event(new MultiplayerGameAction($this->getGameActionData($action, $request)));
        }

        return $this;
    }

    public function complete(): MultiplayerGameService
    {
        $gameable = $this->getGameable();
        $userGames = $gameable->games()->with('account')->get();

        // if no one made a bet
        if ($userGames->count() == 0) {
            // create next multiplayer game
            $this->createNextMultiplayerGame();

            // broadcast action event
            event(new MultiplayerGameAction($this->getGameActionData(__FUNCTION__)));
        // if there were bets in this game
        } elseif ($userGames->where('is_in_progress', TRUE)->count() > 0) {
            $this->beforeComplete();

            // make changes in a single DB transaction
            DB::transaction(function () use ($gameable, $userGames) {
                // loop through all individual games
                $userGames->each(function ($game) use ($gameable) {
                    info(sprintf(
                        'Complete %s %d, game %d, gameable %d, user %d, account %d',
                        class_basename(get_called_class()),
                        $this->getMultiplayerGame()->id,
                        $game->id,
                        $gameable->id,
                        $game->account->user_id,
                        $game->account->id)
                    );

                    // calculate the game result, mark game as completed and save it
                    tap($this->calculateResult($game, $gameable), function ($game) {
                        $game->is_completed = TRUE;

                        // make account transaction if necessary
                        // important to make the transaction before the game model is saved
                        AccountTransaction::create(
                            $game->account,
                            $game,
                            $game->win - $game->getOriginal('win'),
                            FALSE
                        );
                    })->save();

                    if ($game->account_id == $this->getUserAccount()->id) {
                        $this->setGame($game);
                    }

                    // throw new GamePlayed event
                    event(new GamePlayed($game));
                });

                // save gameable
                $gameable->save();

                // create next multiplayer game
                $this->createNextMultiplayerGame();
            });

            // broadcast action event
            event(new MultiplayerGameAction($this->getGameActionData(__FUNCTION__)));
        // if the linked games are already processed
        } elseif ($game = $userGames->where('account_id', $this->getUserAccount()->id)->first()) {
            $this->setGame($game);
        }

        return $this;
    }
}
