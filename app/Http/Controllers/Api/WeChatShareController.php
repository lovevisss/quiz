<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeChatJssdkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WeChatShareController extends Controller
{
    public function __construct(private readonly WeChatJssdkService $weChatJssdkService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url:http,https'],
        ]);

        if (! $this->weChatJssdkService->isConfigured()) {
            return response()->json([
                'enabled' => false,
                'message' => '当前环境尚未配置微信分享。',
            ]);
        }

        try {
            return response()->json([
                'enabled' => true,
                'config' => $this->weChatJssdkService->makeConfig((string) $validated['url']),
            ]);
        } catch (RuntimeException) {
            return response()->json([
                'enabled' => false,
                'message' => '暂时无法生成微信分享配置，请稍后重试。',
            ], 503);
        }
    }
}

