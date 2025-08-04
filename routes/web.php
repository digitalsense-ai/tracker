use App\Http\Controllers\TradeResultController;

Route::get('/results', [TradeResultController::class, 'index'])->name('results');
