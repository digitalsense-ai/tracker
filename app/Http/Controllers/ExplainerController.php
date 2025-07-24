namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExplainerController extends Controller
{
    public function index()
    {
        $terms = [
            ['label' => 'RVOL', 'description' => 'Relative Volume - hvor meget volumen der handles i dag sammenlignet med gennemsnittet. RVOL > 1 betyder over gennemsnit.'],
            ['label' => 'GAP', 'description' => 'Prisgap fra lukkepris i går til åbningspris i dag, målt i procent.'],
            ['label' => 'TP1', 'description' => 'Take Profit niveau 1 – første mål for profit.'],
            ['label' => 'TP2', 'description' => 'Take Profit niveau 2 – andet mål for profit.'],
            ['label' => 'TP3', 'description' => 'Take Profit niveau 3 – tredje mål for profit.'],
            ['label' => 'SL', 'description' => 'Stop Loss – det niveau hvor vi automatisk lukker en handel for at undgå større tab.'],
            ['label' => 'Pullback', 'description' => 'Et midlertidigt fald i pris efter breakout. Bruges til entry ved retest.'],
            ['label' => 'Breakout', 'description' => 'Når prisen bryder ud af en range – typisk over høj/lav fra åbningsinterval.'],
            ['label' => 'Retest', 'description' => 'Når prisen vender tilbage til breakout-niveauet og bekræfter niveauet som støtte/modstand.'],
            ['label' => 'Entry', 'description' => 'Det tidspunkt hvor en handel aktiveres.'],
            ['label' => 'Exit', 'description' => 'Det tidspunkt hvor vi lukker en handel (via TP eller SL).'],
            ['label' => 'Forecast', 'description' => 'Et setups tidlige fase, hvor aktien udviser tegn på potentiel bevægelse, men endnu ikke har brudt ud.'],
            ['label' => 'Gap Up', 'description' => 'En aktie åbner højere end gårsdagens luk – ofte tegn på styrke.'],
            ['label' => 'Gap Down', 'description' => 'En aktie åbner lavere end gårsdagens luk – ofte tegn på svaghed.'],
            ['label' => 'Consolidation', 'description' => 'Pris bevæger sig sidelæns i et stramt interval. Ofte før breakout.'],
            ['label' => 'Volatility Squeeze', 'description' => 'Volatilitet er lav og strammer sig sammen – kan føre til eksplosiv bevægelse.'],
            ['label' => 'Breakout Ready', 'description' => 'Aktien er tæt på at bryde ud af vigtig zone.'],
            ['label' => 'Mean Revert', 'description' => 'Aktien forventes at vende tilbage mod sit gennemsnit efter stor bevægelse.']
        ];

        return view('explainer', compact('terms'));
    }
}
