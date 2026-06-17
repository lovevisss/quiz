<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
class WeChatJssdkService
{
    public function isConfigured(): bool
    {
        return $this->appId() !== '' && $this->appSecret() !== '';
    }
    /**
     * @return array{appId: string, timestamp: int, nonceStr: string, signature: string, jsApiList: array<int, string>}
     */
    public function makeConfig(string $url): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('WeChat share is not configured.');
        }
        $normalizedUrl = preg_replace('/#.*$/', '', trim($url)) ?: trim($url);
        $timestamp = time();
        $nonceStr = Str::random(16);
        $ticket = $this->jsapiTicket();
        $signature = sha1(sprintf(
            'jsapi_ticket=%s&noncestr=%s&timestamp=%d&url=%s',
            $ticket,
            $nonceStr,
            $timestamp,
            $normalizedUrl,
        ));
        return [
            'appId' => $this->appId(),
            'timestamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'signature' => $signature,
            'jsApiList' => [
                'updateTimelineShareData',
                'updateAppMessageShareData',
            ],
        ];
    }
    private function accessToken(): string
    {
        return Cache::remember('wechat.official_account.access_token', now()->addMinutes(100), function (): string {
            $response = Http::get('https://api.weixin.qq.com/cgi-bin/token', [
                'grant_type' => 'client_credential',
                'appid' => $this->appId(),
                'secret' => $this->appSecret(),
            ])->throw();
            $payload = $response->json();
            if (! is_array($payload) || empty($payload['access_token'])) {
                throw new RuntimeException('Failed to fetch WeChat access token.');
            }
            return (string) $payload['access_token'];
        });
    }
    private function jsapiTicket(): string
    {
        return Cache::remember('wechat.official_account.jsapi_ticket', now()->addMinutes(100), function (): string {
            $response = Http::get('https://api.weixin.qq.com/cgi-bin/ticket/getticket', [
                'access_token' => $this->accessToken(),
                'type' => 'jsapi',
            ])->throw();
            $payload = $response->json();
            if (! is_array($payload) || ($payload['errcode'] ?? -1) !== 0 || empty($payload['ticket'])) {
                throw new RuntimeException('Failed to fetch WeChat JSAPI ticket.');
            }
            return (string) $payload['ticket'];
        });
    }
    private function appId(): string
    {
        return (string) config('services.wechat.official_account.app_id', '');
    }
    private function appSecret(): string
    {
        return (string) config('services.wechat.official_account.app_secret', '');
    }
}
