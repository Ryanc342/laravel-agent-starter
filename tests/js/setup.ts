import { cleanup } from '@testing-library/react';
import { afterEach } from 'vite-plus/test';

// Unmount what each test rendered, so its listeners never reach the next test.
afterEach(() => {
    cleanup();
});
