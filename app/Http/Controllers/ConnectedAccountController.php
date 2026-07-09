<?php

namespace App\Http\Controllers;

use App\Models\AutomationLog;
use App\Models\ConnectedAccount;
use App\Services\GmailConnectionService;
use App\Services\TelegramConnectionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConnectedAccountController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');

        return view('dashboard.accounts', [
            'accounts' => ConnectedAccount::where('business_id', $business->id)
                ->where('status', 'connected')
                ->latest('connected_at')
                ->latest()
                ->get(),
        ]);
    }

    public function fakeConnect(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in(['Instagram', 'Facebook', 'WhatsApp'])],
            'account_name' => ['nullable', 'string', 'max:80'],
        ]);

        $platform = $validated['platform'];
        $platformCount = ConnectedAccount::where('business_id', $business->id)
            ->where('platform', $platform)
            ->where('status', 'connected')
            ->count();
        $accountNumber = $platformCount + 1;
        $platformSlug = str($platform)->lower()->slug();
        $accountName = $validated['account_name'] ?: $business->name.' '.$platform.' '.($accountNumber > 1 ? '#'.$accountNumber : 'Main');

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => $platform,
            'account_name' => $accountName,
            'external_account_id' => strtolower($business->slug.'-'.$platformSlug.'-'.$accountNumber.'-'.str()->random(6)),
            'page_id' => $platform === 'Facebook' ? 'demo-page-'.$business->id.'-'.$accountNumber : null,
            'phone_number_id' => $platform === 'WhatsApp' ? 'demo-phone-'.$business->id.'-'.$accountNumber : null,
            'status' => 'connected',
            'connected_at' => now(),
            'access_token' => 'fake-demo-token',
        ]);

        AutomationLog::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'account_connected',
            'status' => 'success',
            'message' => $platform.' demo account connected.',
        ]);

        return back()->with('status', $platform.' account added.');
    }

    public function redirectToGmail(Request $request, GmailConnectionService $gmailConnectionService)
    {
        $business = $request->attributes->get('currentBusiness');
        $state = Str::random(48);

        session([
            'gmail_oauth_state' => $state,
            'gmail_oauth_business_id' => $business->id,
        ]);

        return redirect()->away($gmailConnectionService->buildRedirectUrl($state));
    }

    public function handleGmailCallback(Request $request, GmailConnectionService $gmailConnectionService)
    {
        $business = $request->attributes->get('currentBusiness');

        if ($request->filled('error')) {
            return redirect()
                ->route('dashboard.accounts')
                ->with('error', 'Gmail connection was cancelled.');
        }

        if (
            ! hash_equals((string) session('gmail_oauth_state'), (string) $request->query('state'))
            || (int) session('gmail_oauth_business_id') !== (int) $business->id
        ) {
            return redirect()
                ->route('dashboard.accounts')
                ->with('error', 'Gmail connection could not be verified. Please try again.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $tokens = $gmailConnectionService->exchangeAuthorizationCode($validated['code']);
            $account = $gmailConnectionService->connect($business, $tokens);
        } catch (ConnectionException $exception) {
            report($exception);

            return redirect()
                ->route('dashboard.accounts')
                ->with('error', 'Gmail connection failed because this machine could not reach Google OAuth. Check firewall, proxy, VPN, or network access to oauth2.googleapis.com:443.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard.accounts')
                ->with('error', 'Gmail connection failed. Please check your Google settings and try again.');
        } finally {
            session()->forget(['gmail_oauth_state', 'gmail_oauth_business_id']);
        }

        return redirect()
            ->route('dashboard.accounts')
            ->with('status', $account->account_name.' connected.');
    }

    public function syncGmailAccount(Request $request, ConnectedAccount $account, GmailConnectionService $gmailConnectionService)
    {
        $business = $request->attributes->get('currentBusiness');

        abort_unless($account->business_id === $business->id, 403);
        abort_unless($account->platform === 'gmail', 404);

        try {
            $result = $gmailConnectionService->syncRecentInboxMessages($account);
        } catch (ConnectionException $exception) {
            report($exception);

            AutomationLog::create([
                'business_id' => $business->id,
                'connected_account_id' => $account->id,
                'event_type' => 'gmail_sync',
                'status' => 'failed',
                'message' => 'Gmail sync failed because Google could not be reached.',
            ]);

            return back()->with('error', 'Gmail sync failed because this machine could not reach Google Gmail API. Check firewall, proxy, VPN, or network access to gmail.googleapis.com:443.');
        } catch (\Throwable $exception) {
            report($exception);

            AutomationLog::create([
                'business_id' => $business->id,
                'connected_account_id' => $account->id,
                'event_type' => 'gmail_sync',
                'status' => 'failed',
                'message' => 'Gmail sync failed.',
            ]);

            return back()->with('error', 'Gmail sync failed. Please reconnect Gmail and try again.');
        }

        return back()->with('status', "Gmail sync complete: {$result['imported']} imported, {$result['skipped']} skipped.");
    }

    public function connectTelegram(Request $request, TelegramConnectionService $telegramConnectionService)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'account_name' => ['nullable', 'string', 'max:80'],
            'bot_token' => ['required', 'string', 'max:220'],
            'bot_username' => ['nullable', 'string', 'max:80'],
        ]);

        $botUsername = trim((string) ($validated['bot_username'] ?? ''), " \t\n\r\0\x0B@");
        $platformCount = ConnectedAccount::where('business_id', $business->id)
            ->where('platform', 'Telegram')
            ->where('status', 'connected')
            ->count();
        $accountNumber = $platformCount + 1;
        $accountName = $validated['account_name']
            ?: ($botUsername ? '@'.$botUsername : $business->name.' Telegram '.($accountNumber > 1 ? '#'.$accountNumber : 'Bot'));

        $account = ConnectedAccount::create([
            'business_id' => $business->id,
            'platform' => 'Telegram',
            'account_name' => $accountName,
            'external_account_id' => $botUsername ? '@'.$botUsername : strtolower($business->slug.'-telegram-'.$accountNumber.'-'.str()->random(6)),
            'status' => 'connected',
            'connected_at' => now(),
            'access_token' => $validated['bot_token'],
            'provider_meta' => [
                'bot_username' => $botUsername ? '@'.$botUsername : null,
                'webhook_secret' => Str::random(40),
            ],
        ]);

        AutomationLog::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'account_connected',
            'status' => 'success',
            'message' => 'Telegram account connected.',
        ]);

        try {
            $webhook = $telegramConnectionService->registerWebhook($account);
        } catch (ConnectionException $exception) {
            report($exception);

            $account->forceFill([
                'provider_meta' => array_merge($account->provider_meta ?? [], [
                    'webhook_status' => 'network_failed',
                    'webhook_url' => $telegramConnectionService->webhookUrl($account),
                ]),
            ])->save();

            return back()->with('error', 'Telegram account saved, but this machine could not reach Telegram to register the webhook.');
        } catch (\Throwable $exception) {
            report($exception);

            $account->forceFill([
                'provider_meta' => array_merge($account->provider_meta ?? [], [
                    'webhook_status' => 'failed',
                    'webhook_url' => $telegramConnectionService->webhookUrl($account),
                ]),
            ])->save();

            return back()->with('error', 'Telegram account saved, but webhook registration failed. Check the bot token and APP_URL.');
        }

        return back()->with($webhook['ok'] ? 'status' : 'error', $webhook['message']);
    }

    public function disconnect(Request $request, ConnectedAccount $account, TelegramConnectionService $telegramConnectionService)
    {
        $business = $request->attributes->get('currentBusiness');

        abort_unless($account->business_id === $business->id, 403);

        if ($account->platform === 'Telegram') {
            try {
                $telegramConnectionService->forgetWebhook($account);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $account->update([
            'status' => 'disconnected',
            'connected_at' => null,
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
        ]);

        AutomationLog::create([
            'business_id' => $business->id,
            'connected_account_id' => $account->id,
            'event_type' => 'account_disconnected',
            'status' => 'success',
            'message' => $account->platform.' account disconnected.',
        ]);

        return back()->with('status', $account->platform.' account disconnected.');
    }
}
