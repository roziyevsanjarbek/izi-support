<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\CalendarIndexController;
use App\Http\Controllers\Integrations\Zanjeer\QueryController;
use App\Http\Controllers\Messages\ConversationController;
use App\Http\Controllers\Messages\ConversationMessageController;
use App\Http\Controllers\Page\PageController;
use App\Http\Controllers\Page\UserController;
use App\Http\Controllers\Requests\RequestBidController;
use App\Http\Controllers\Requests\RequestController;
use App\Http\Controllers\Tasks\QueryTaskController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Users\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});
Route::get('/e', function () {
    $users = User::query()
        ->where('email', 'like', '%sales%')
        ->get(['id', 'name', 'email', 'department']);

    return response()->json($users);
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:users');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/calendar', [TaskController::class, 'calendar'])->name('calendar');
        Route::get('/calendar/events', [TaskController::class, 'calendarEvents'])->name('tasks.calendar.events');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::patch('/{task}/status', [TaskController::class, 'update'])->name('status.update');
        Route::put('/{task}/details', [TaskController::class, 'updateDetails'])->name('update-details');
        Route::post('/{task}/complete', [TaskController::class, 'complete'])->name('complete');
        Route::post('/{task}/reject', [TaskController::class, 'reject'])->name('reject');
        Route::post('/query-tasks', [QueryTaskController::class, 'store'])->name('query-tasks.store');
        Route::get('/query-tasks', [QueryTaskController::class, 'index'])->name('query-tasks.index');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
    });

    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [RequestController::class, 'index'])->name('index');
        Route::post('/', [RequestController::class, 'store'])->name('store');
        Route::get('/{request}', [RequestController::class, 'show'])->name('show');
        Route::delete('/{request}', [RequestController::class, 'destroy'])->name('destroy');
        Route::post('/{request}/offers', [RequestBidController::class, 'store'])->name('offers.store');
    });

    //Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.update');
    Route::get('/profile/telegram/connect', [ProfileController::class, 'connectTelegram'])->name('profile.telegram.connect');
    Route::delete('/profile/telegram', [ProfileController::class, 'disconnectTelegram'])->name('profile.telegram.disconnect');

    Route::get('/queries', [QueryController::class, 'index'])->name('integrations.zanjeer.queries');
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [CalendarIndexController::class, 'index'])->name('index');

        Route::get('/events', [CalendarController::class, 'events'])->name('events.index');
        Route::post('/events', [CalendarController::class, 'store'])->name('events.store');
        Route::patch('/events/{event}', [CalendarController::class, 'update'])->name('events.update');
        Route::post('/events/{event}/mark/complete', [CalendarController::class, 'complete'])->name('events.complete');
        Route::post('/events/{event}/mark/not-completed', [CalendarController::class, 'not_complete'])->name('events.not_complete');
        Route::patch('/events/{event}/done', [CalendarController::class, 'done'])->name('events.done');
        Route::delete('/events/{event}', [CalendarController::class, 'destroy'])->name('events.destroy');

        Route::get('/events/{event}/modal', [CalendarController::class, 'modal']);

    });
    Route::prefix('messages')->name('messages.')->middleware('auth')->group(function () {
    Route::prefix('conversations')->name('conversations.')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        Route::get('/poll', [ConversationController::class, 'poll'])->name('poll');
        Route::get('/users', [ConversationController::class, 'users'])->name('users');

        Route::post('/', [ConversationController::class, 'store'])->name('store');
        Route::get('{conversation}', [ConversationController::class, 'show'])->name('show');
        Route::get('start/{user}', [ConversationController::class, 'start'])->name('start');

        Route::patch('{conversation}/pin', [ConversationController::class, 'pin'])->name('pin');
        Route::patch('{conversation}/unpin', [ConversationController::class, 'unpin'])->name('unpin');
        Route::patch('{conversation}/toggle-notifications', [ConversationController::class, 'toggleNotifications'])->name('toggle-notifications');

        Route::prefix('{conversation}/messages')->name('messages.')->group(function () {
            Route::get('/', [ConversationMessageController::class, 'messages'])->name('index');
            Route::post('/', [ConversationMessageController::class, 'store'])->name('store');
            Route::put('{message}/update', [ConversationMessageController::class, 'update'])->name('update');
            Route::delete('{message}/destroy', [ConversationMessageController::class, 'destroy'])->name('destroy');

            Route::post('/location', [ConversationMessageController::class, 'storeLocation'])->name('location');
        });
    });

    Route::get('/search/messages', [ConversationMessageController::class, 'search'])->name('search.messages');
    Route::post('/messages/resend', [ConversationMessageController::class, 'resend'])->name('messages.resend');
});
});
