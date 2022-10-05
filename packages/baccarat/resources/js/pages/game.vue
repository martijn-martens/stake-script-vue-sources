<template>
  <div v-if="ready" class="d-flex flex-column fill-height py-3">
    <div class="d-flex justify-center fill-height align-center">
      <hand
        :title="$t('Player')"
        :cards="playerHandCards"
        :score="playerHandScore"
        :result="playerHandResult"
        :result-class="winType === 0 ? 'primary text--primary' : (winType === 1 ? 'warning' : (winType === 2 ? 'error' : ''))"
        :bet="currentBetType === 0 ? betValue : 0"
        :win="winType === 0 ? win : 0"
        class="hand-container"
      />
      <hand
        :title="$t('Tie')"
        :cards="[]"
        :bet="currentBetType === 1 ? betValue : 0"
        :win="winType === 1 ? win : 0"
        class="hand-container hand-tie"
      >
        <template v-slot>
          <div class="vertical-space"></div>
        </template>
      </hand>
      <hand
        :title="$t('Banker')"
        :cards="bankerHandCards"
        :score="bankerHandScore"
        :result="bankerHandResult"
        :result-class="winType === 2 ? 'primary text--primary' : (winType === 1 ? 'warning' : (winType ===0 ? 'error' : ''))"
        :bet="currentBetType === 2 ? betValue : 0"
        :win="winType === 2 ? win : 0"
        class="hand-container"
      />
    </div>
    <div class="d-flex justify-center flex-wrap">
      <v-btn-toggle
        v-model="betType"
        active-class="primary--text"
        mandatory
        rounded
        group
      >
        <v-btn
          v-for="(title, i) in betTypes"
          :key="i"
          :disabled="playing"
          class="mx-1 my-2 my-lg-0"
          small
          @click="sound(clickSound)"
        >
          {{ title }}
        </v-btn>
      </v-btn-toggle>
    </div>
    <play-controls :loading="loading" :playing="playing" @play="play" />
  </div>
  <div v-else class="d-flex fill-height justify-center align-center">
    <block-preloader />
  </div>
</template>

<script>
import axios from 'axios'
import { mapState, mapActions } from 'vuex'
import FormMixin from '~/mixins/Form'
import GameMixin from '~/mixins/Game'
import SoundMixin from '~/mixins/Sound'
import Hand from '~/components/Games/Cards/Hand'
import { sleep } from '~/plugins/utils'
import clickSound from '~/../audio/common/click.wav'
import dealSound from 'packages/baccarat/resources/audio/deal.wav'
import swooshSound from 'packages/baccarat/resources/audio/swoosh.wav'
import winSound from 'packages/baccarat/resources/audio/win.wav'
import loseSound from 'packages/baccarat/resources/audio/lose.wav'
import pushSound from 'packages/baccarat/resources/audio/push.wav'
import PlayControls from '~/components/Games/PlayControls'
import BlockPreloader from '~/components/BlockPreloader'

export default {
  name: 'Baccarat',

  components: { BlockPreloader, PlayControls, Hand },

  mixins: [FormMixin, GameMixin, SoundMixin],

  data () {
    return {
      clickSound,
      loading: false,
      playing: false,
      ready: false,
      betValue: 0,
      betType: 0,
      betTypes: [
        this.$t('Player'),
        this.$t('Tie'),
        this.$t('Banker')
      ],
      currentBetType: -1,
      winType: 0,
      win: 0,

      playerHandCards: ['D3', 'H5'],
      playerHandScore: 8,
      playerHandResult: this.$t('Win'),

      bankerHandCards: ['S6', 'CA'],
      bankerHandScore: 7,
      bankerHandResult: this.$t('Lose')
    }
  },

  computed: {
    ...mapState('auth', ['account']),
    playerHandCount () {
      return this.playerHandCards.length
    },
    bankerHandCount () {
      return this.bankerHandCards.length
    }
  },

  async created () {
    await this.loadCardDeck()
    this.ready = true
  },

  methods: {
    ...mapActions({
      updateUserAccountBalance: 'auth/updateUserAccountBalance',
      setProvablyFairGame: 'provably-fair/set'
    }),
    async play (bet) {
      this.loading = true
      this.playing = true

      // update user balance
      this.updateUserAccountBalance(this.account.balance - bet)

      // clear previous game results
      this.playerHandCards = []
      this.bankerHandCards = []

      this.sound(swooshSound)

      this.betValue = 0
      this.win = 0
      this.currentBetType = -1
      this.winType = -1

      this.playerHandScore = -1
      this.bankerHandScore = -1

      this.playerHandResult = ''
      this.bankerHandResult = ''

      await sleep(500)

      // API request params
      const endpoint = this.getRoute('play')
      const requestParams = { hash: this.provablyFairGame.hash, bet, bet_type: this.betType }

      // execute the action
      const { data: game } = await axios.post(endpoint, requestParams)

      this.loading = false

      let animationDelay = 0

      const playerHandCountReceived = game.gameable.player_hand.length
      const bankerHandCountReceived = game.gameable.banker_hand.length

      // 1st card
      this.playerHandCards.push(game.gameable.player_hand[0])
      this.bankerHandCards.push(game.gameable.banker_hand[0])
      this.currentBetType = this.betType
      this.betValue = game.bet
      this.sound(dealSound)

      // 2nd card
      setTimeout(() => {
        this.playerHandCards.push(game.gameable.player_hand[1])
        this.bankerHandCards.push(game.gameable.banker_hand[1])
        this.playerHandScore = game.gameable.player_score
        this.bankerHandScore = game.gameable.banker_score
        this.sound(dealSound)
      }, animationDelay += 500)

      if (playerHandCountReceived > 2) {
        // 3rd player card
        setTimeout(() => {
          this.playerHandCards.push(game.gameable.player_hand[2])
          this.playerHandScore = game.gameable.player_score
          this.sound(dealSound)
        }, animationDelay += 750)
      }

      if (bankerHandCountReceived > 2) {
        // 3rd banker card
        setTimeout(() => {
          this.bankerHandCards.push(game.gameable.banker_hand[2])
          this.bankerHandScore = game.gameable.banker_score
          this.sound(dealSound)
        }, animationDelay += 750)
      }

      this.setProvablyFairGame({ key: this.gamePackageId, game: game.pf_game })

      // display banker score and result for each hand
      setTimeout(() => {
        this.playerHandResult = game.gameable.player_result
        this.bankerHandResult = game.gameable.banker_result

        this.winType = game.gameable.win_type
        this.win = game.win

        this.playing = false

        // update balance
        this.updateUserAccountBalance(game.account.balance)

        // play sound
        if (game.win > game.bet) {
          this.sound(winSound)
        } else if (game.win === game.bet) {
          this.sound(pushSound)
        } else {
          this.sound(loseSound)
        }
      }, animationDelay)
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~/../sass/_variables.scss';

.game-baccarat {
  .hand-container {
    min-width: calc(#{$playing-card-width} * 2 + 25px);
    min-height: calc(#{$playing-card-height} + 3em);
  }

  .vertical-space {
    min-height: $playing-card-height;
  }

  @media (max-width: 599px) {
    .hand-container {
      min-width: 5.5em;
    }

    .hand-tie {
      margin-left: -1em;
    }
  }
}
</style>
