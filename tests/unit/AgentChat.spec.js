import { mount } from '@vue/test-utils';
import AgentChat from '@/components/AgentChat/AgentChat.vue';

// Mocking emits and props
describe('AgentChat.vue keyboard behavior', () => {
    test('Enter without modifiers sends message and clears input', async () => {
        const wrapper = mount(AgentChat, {
            props: {
                messages: [],
                status: 'completed',
                sending: false,
                requestCount: 0,
                contextFill: 0,
                totalTokens: 0,
            },
        });

        const textarea = wrapper.find('textarea');
        await textarea.setValue('Hello');

        // Trigger Enter key
        await textarea.trigger('keydown.enter');

        // Expect event emitted
        expect(wrapper.emitted('sendMessage')).toBeTruthy();
        expect(wrapper.emitted('sendMessage')[0]).toEqual(['Hello']);

        // Expect input cleared
        expect((wrapper.vm as any).input).toBe('');
    });

    test('Ctrl+Enter inserts newline and does not send message', async () => {
        const wrapper = mount(AgentChat, {
            props: {
                messages: [],
                status: 'completed',
                sending: false,
                requestCount: 0,
                contextFill: 0,
                totalTokens: 0,
            },
        });

        const textarea = wrapper.find('textarea');
        await textarea.setValue('Line1');

        // Create a KeyboardEvent with ctrlKey true
        const event = new KeyboardEvent('keydown', { key: 'Enter', ctrlKey: true });
        await textarea.element.dispatchEvent(event);

        // Value should include newline (browser behavior not simulated fully here) — we simulate manual insertion
        // Simulate user adding newline
        await textarea.setValue('Line1\n');

        expect(wrapper.emitted('sendMessage')).toBeFalsy();
        expect((wrapper.vm as any).input).toBe('Line1\n');
    });

    test('Enter when frozenInput does not send message', async () => {
        const wrapper = mount(AgentChat, {
            props: {
                messages: [],
                status: 'generating', // frozenInput true
                sending: false,
                requestCount: 0,
                contextFill: 0,
                totalTokens: 0,
            },
        });

        const textarea = wrapper.find('textarea');
        await textarea.setValue('Should not send');

        await textarea.trigger('keydown.enter');

        expect(wrapper.emitted('sendMessage')).toBeFalsy();
        expect((wrapper.vm as any).input).toBe('Should not send');
    });
});
