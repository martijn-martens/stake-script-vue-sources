<template>
  <v-card>
    <v-toolbar>
      <v-toolbar-title>
        {{ $t('Game information') }}
      </v-toolbar-title>
      <v-spacer />
      <v-btn icon @click="$emit('close')">
        <v-icon>mdi-close</v-icon>
      </v-btn>
      <template v-slot:extension>
        <v-tabs v-model="infoTab" centered hide-slider>
          <v-tab href="#tab-about">
            {{ $t('How to play') }}
          </v-tab>
          <v-tab href="#tab-paytable">
            {{ $t('Paytable') }}
          </v-tab>
        </v-tabs>
      </template>
    </v-toolbar>
    <v-tabs-items v-model="infoTab">
      <v-tab-item value="tab-about">
        <v-card flat>
          <v-card-text class="about-text">
            <p>
              {{ $t('The objective in Baccarat is to correctly guess which of three possible propositions will win the round: player, banker or tie.') }}
              {{ $t('Whichever hand is closest to nine is the winner.') }}
              {{ $t('Cards are given point values as follows.') }}
              {{ $t('Face cards and tens have no value.') }}
              {{ $t('2 - 9 are counted at face value.') }}
              {{ $t('Aces are valued at 1.') }}
              {{ $t('Counts that reach double digits drop the first digit and are revalued (so 15 becomes 5).') }}
            </p>
            <p>
              {{ $t('For each coup, two cards are dealt face up.') }}
              {{ $t('If either the player or banker or both achieve a total of 8 or 9 at this stage, the coup is finished and the result is announced: a player win, a banker win, or tie.') }}
              {{ $t('If neither hand has eight or nine, the drawing rules are applied to determine whether the player should receive a third card.') }}
              {{ $t('Then, based on the value of any card drawn to the player, the drawing rules are applied to determine whether the banker should receive a third card.') }}
              {{ $t('The coup is then finished, the outcome is announced, and winning bets are paid out.') }}
            </p>
            <p>
              {{ $t('If the player has an initial total of 0–5, they draw a third card. If the player has an initial total of 6 or 7, they stand.') }}
              {{ $t('If the player stood, the banker regards only their own hand and acts according to the same rule as the player.') }}
              {{ $t('If the player drew a third card, the banker acts according to the following rules.') }}
              {{ $t('If the banker total is 2 or less, then the banker draws a card, regardless of what the player third card is.') }}
              {{ $t('If the banker total is 3, then the banker draws a third card unless the player third card was an 8.') }}
              {{ $t('If the banker total is 4, then the banker draws a third card if the player third card was 2, 3, 4, 5, 6, 7.') }}
              {{ $t('If the banker total is 5, then the banker draws a third card if the player third card was 4, 5, 6, or 7.') }}
              {{ $t('If the banker total is 6, then the banker draws a third card if the player third card was a 6 or 7.') }}
              {{ $t('If the banker total is 7, then the banker stands.') }}
            </p>
          </v-card-text>
        </v-card>
      </v-tab-item>
      <v-tab-item value="tab-paytable">
        <v-card flat>
          <v-card-text>
            <v-simple-table>
              <thead>
              <tr>
                <th>{{ $t('Result') }}</th>
                <th class="text-right">{{ $t('Payout') }}</th>
              </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{{ $t('Player bet win') }}</td>
                  <td class="text-right">{{ playerBetPayout }}</td>
                </tr>
                <tr>
                  <td>{{ $t('Tie bet win') }}</td>
                  <td class="text-right">{{ tieBetPayout }}</td>
                </tr>
                <tr>
                  <td>{{ $t('Banker bet win') }}</td>
                  <td class="text-right">{{ bankerBetPayout }}</td>
                </tr>
              </tbody>
            </v-simple-table>
          </v-card-text>
        </v-card>
      </v-tab-item>
    </v-tabs-items>
  </v-card>
</template>

<script>
import { config } from '~/plugins/config'

export default {
  data () {
    return {
      infoTab: 'tab-about',
      playerBetPayout: config('baccarat.payouts.player') - 1 + ':1',
      tieBetPayout: config('baccarat.payouts.tie') - 1 + ':1',
      bankerBetPayout: config('baccarat.payouts.banker') - 1 + ':1'
    }
  }
}
</script>
