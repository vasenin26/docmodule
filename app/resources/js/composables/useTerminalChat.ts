import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { createApi } from '@/services/api/Api';
import { TerminalSendCommandRequest } from '@/services/api/request/Terminal/TerminalSendCommandRequest';
import { TerminalGetChatStateRequest } from '@/services/api/request/Terminal/TerminalGetChatStateRequest';
import type { LLMMessage } from '@/types';

export function useTerminalChat(terminalId: number) {
    const messages = ref<LLMMessage[]>([]);
    const status = ref<string>('loading');
    const sending = ref<boolean>(false);
    const requestCount = ref<number>(0);
    const pollingState = ref<boolean>(false);
    
    const api = createApi();
    let pollingTimeout: any = null;
    
    async function loadChatState() {
        if (!terminalId) return;
        
        try {
            const req = new TerminalGetChatStateRequest(terminalId);
            const result = await req.call(api);
            status.value = result.status;
            messages.value = result.messages || [];
            requestCount.value++;
            
            if (status.value === 'completed') {
                stopPolling();
            }
        } catch (error) {
            console.error('Ошибка при загрузке состояния чата:', error);
        }
    }
    
    async function sendCommand(command: string) {
        if (!terminalId || !command.trim()) return;
        
        sending.value = true;
        try {
            const req = new TerminalSendCommandRequest(terminalId, { message: command });
            await req.call(api);
            startPolling();
        } catch (error) {
            console.error('Ошибка при отправке команды:', error);
        } finally {
            sending.value = false;
        }
    }
    
    function startPolling() {
        if (pollingState.value) return;
        pollingState.value = true;
        poll();
    }
    
    function stopPolling() {
        pollingState.value = false;
        if (pollingTimeout) {
            clearTimeout(pollingTimeout);
            pollingTimeout = null;
        }
    }
    
    async function poll() {
        if (!pollingState.value) return;
        
        await loadChatState();
        
        if (pollingState.value) {
            pollingTimeout = setTimeout(() => poll(), 700);
        }
    }
    
    // Фильтрация сообщений: только user, assistant и result
    const filteredMessages = computed(() => {
        return messages.value.filter(m => 
            m.type === 'user' || m.type === 'assistant' || m.type === 'result'
        );
    });
    
    onMounted(() => {
        loadChatState();
        startPolling();
    });
    
    onBeforeUnmount(() => {
        stopPolling();
    });
    
    return {
        messages: filteredMessages,
        status,
        sending,
        requestCount,
        sendCommand,
        startPolling,
        stopPolling,
    };
}

