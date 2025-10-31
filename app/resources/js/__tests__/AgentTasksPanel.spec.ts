import { describe, it, expect, vi } from 'vitest';
import { createApi } from '@/service/api/Api';
import { AgentTasksCheckRequest } from '@/service/api/request/Task/AgentTasksCheckRequest';
import * as poller from '@/services/agentTasksPoller';

vi.mock('@/service/api/Api');

describe('AgentTasks poller', () => {
    it('sends POST with ids from localStorage and schedules next call after promise resolves', async () => {
        // Setup fake timers
        const fakeTimer = vi.useFakeTimers();

        // Prepare localStorage with ids
        localStorage.setItem('agent_tasks_panel_ids', JSON.stringify([{ id: '1' }, { id: '2' }]));

        // Mock createApi and request behavior
        const mockExecute = vi.fn().mockResolvedValue([
            { id: '1', chat_id: 10, agent_task_id: 1, raw_status: 'processing', updated_at: new Date().toISOString() },
        ]);

        // @ts-ignore
        createApi.mockImplementation(() => ({ execute: mockExecute }));

        // Spy on req.call to ensure it's used
        const spyReq = vi.spyOn(AgentTasksCheckRequest.prototype, 'call');

        // Start poller
        poller.startPoller();

        // Fast-forward timers to allow tick to run
        await vi.runOnlyPendingTimersAsync();

        // Ensure request was made
        expect(spyReq).toHaveBeenCalled();
        expect(mockExecute).toHaveBeenCalled();

        // Clean up
        poller.stopPoller();
        fakeTimer.restore();
    });
});
