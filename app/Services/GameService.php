<?php

namespace App\Services;

use App\Events\GamePlayed;
use App\Models\Account;
use App\Models\Game;
use App\Models\ProvablyFairGame;
use App\Models\User;

abstract class GameService
{
    private $user;
    private $provablyFairGame;

    protected $appends = []; // attributes to append to the response object
    protected $makeVisible = []; // hidden attributes of the gameable object, which should be made visible upon completion of the game
    protected $gameableClass;
    protected $gameable;

    public function __construct(User $user)
    {
        if (!$this->gameableClass)
            throw new \Exception('Gameable model should be explicitly set in the child class before calling GameService constructor.');

        // check if a specific user is passed in, otherwise get the user from the request
        $this->user = $user->getKey() ? $user : auth()->user();
    }

    public function createProvablyFairGame(string $clientSeed): GameService
    {
        $this->provablyFairGame = new ProvablyFairGame();
        $this->provablyFairGame->secret = $this->makeSecret();
        $this->provablyFairGame->client_seed = $clientSeed;
        $this->provablyFairGame->gameable_type = $this->gameableClass;
        $this->provablyFairGame->save();

        return $this;
    }

    public function loadProvablyFairGame(string $hash): GameService
    {
        $this->provablyFairGame = ProvablyFairGameService::get($hash, $this->gameableClass);
        $this->provablyFairGame->loadMissing(['game', 'game.gameable']);

        return $this;
    }

    /**
     * Create new Game model instance without overwriting the current one (that's why new child GameService class instance is required)
     *
     * @return Game
     */
    protected function createNextGame(): Game
    {
        $childGameService = get_called_class();
        // it's important to pass $user object for playing games by bots,
        // because in this case user will not be taken from the session.
        return (new $childGameService($this->user))->create();
    }

    public function getProvablyFairGame(): ProvablyFairGame
    {
        return $this->provablyFairGame;
    }

    /**
     * Get Game model instance
     *
     * @return Game
     */
    public function getGame(): ?Game
    {
        return $this->getProvablyFairGame()->game;
    }

    public function getGameable()
    {
        return $this->getGame() ? $this->getGame()->gameable : $this->gameable;
    }

    public function unsetGameable(): GameService
    {
        unset($this->getGame()->gameable);

        return $this;
    }

    public function isGameCompleted(): bool
    {
        return $this->getGame()->is_completed ?? FALSE;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserAccount(): Account
    {
        return $this->getUser()->account;
    }

    public function getUserAccountBalance(): float
    {
        return $this->getUserAccount()->balance;
    }

    /**
     * Save the game
     *
     * @param array $gameAttributes
     * @return GameService
     */
    final protected function save(array $gameAttributes): GameService
    {
        // get gameable (expected to be initialized in a child class)
        $gameable = $this->getGameable();

        // save gameable
        $gameable->save();

        // create a new game if it doesn't exist
        $gameExists = boolval($this->getGame());
        if (!$gameExists) {
            $this->provablyFairGame->game = new Game();
            $this->provablyFairGame->game->account()->associate($this->getUserAccount());
            $this->provablyFairGame->game->provablyFairGame()->associate($this->provablyFairGame);
        }

        // calculate transaction amount
        $currentBet = $this->provablyFairGame->game->bet ?? 0;
        $bet = $gameAttributes['bet'] ?? $currentBet;
        $win = $gameAttributes['win'] ?? 0;
        $transactionAmount = $win - $bet + $currentBet;

        // fill all passed arguments
        foreach ($gameAttributes as $key => $value) {
            $this->provablyFairGame->game->{$key} = $value;
        }

        // attach polymorphic relationship for a new game
        if (!$gameExists) {
            $gameable->game()->save($this->provablyFairGame->game);
        // if the game already exists just save it
        } else {
            $this->provablyFairGame->game->save();
        }

        // append extra attributes to JSON (can be modified by child gameable classes)
        $gameable->append($this->appends);
        // append gameable model instance to the game instance
        $this->provablyFairGame->game->gameable = $gameable;

        // create a new account transaction
        $accountService = new AccountService($this->getUserAccount());
        $accountService->createTransaction($this->getGame(), $transactionAmount);

        // if game is completed
        if ($this->isGameCompleted()) {
            // make hidden fields visible
            $this->provablyFairGame->game->gameable->makeVisible($this->makeVisible);

            // create a new provably fair game and pass it to the frontend
            $childGameService = get_called_class();
            $this->provablyFairGame->game->pf_game = (new $childGameService($this->getUser()))->createProvablyFairGame($this->getProvablyFairGame()->client_seed)->getProvablyFairGame();

            // throw new GamePlayed event
            event(new GamePlayed($this->getGame()));
        }

        // make ID hidden
        $this->provablyFairGame->game->makeHidden(['id']);
        $this->provablyFairGame->game->gameable->makeHidden(['id']);

        return $this;
    }

    /**
     * Make a game secret (reels positions, shuffled cards deck) - before applying client seed
     *
     * @return string
     */
    abstract public function makeSecret(): string;

    /**
     * Create a random game
     *
     * @param User $user
     */
    abstract public static function createRandomGame(User $user): void;
}
