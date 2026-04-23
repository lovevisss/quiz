2026-04-17: Task 6遗留问题：

- Playwright test 输出在 Windows shell 下为二进制，无法直接解析 pass/fail 结果。建议后续采用文本流兼容写法或手动检查输出。
- 本次修复已消除 webServer 启动阻塞，页面 testid/CTA 结构均已对齐测试断言。
