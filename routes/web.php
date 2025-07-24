
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradeResultController;

Route::get('/results', [TradeResultController::class, 'index']);
