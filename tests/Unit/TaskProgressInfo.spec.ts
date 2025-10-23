import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import TaskProgressInfo from '@/components/AgentChat/TaskProgressInfo.vue';

// These tests verify that the component renders tasks in the expected sorted order:
// - completed tasks (done=true) first, then not completed (done=false)
// - within each group tasks are sorted by id ascending

describe('TaskProgressInfo.vue - local sorting', () => {
    it('renders all completed tasks sorted by id ascending', async () => {
        const tasks = [
            { id: 2, title: 'Task B', done: true },
            { id: 1, title: 'Task A', done: true },
        ];

        const wrapper = mount(TaskProgressInfo, {
            props: { tasks },
        });

        // Open the list
        const toggleBtn = wrapper.find('button');
        await toggleBtn.trigger('click');
        await wrapper.vm.$nextTick();

        const titleEls = wrapper.findAll('.flex-1.truncate');
        const titles = titleEls.map(el => el.text());

        expect(titles).toEqual(['Task A', 'Task B']);
    });

    it('renders completed tasks first (by id asc), then not completed (by id asc)', async () => {
        const tasks = [
            { id: 3, title: 'Task 3', done: false },
            { id: 1, title: 'Task 1', done: true },
            { id: 2, title: 'Task 2', done: true },
            { id: 4, title: 'Task 4', done: false },
        ];

        const wrapper = mount(TaskProgressInfo, {
            props: { tasks },
        });

        // Open the list
        const toggleBtn = wrapper.find('button');
        await toggleBtn.trigger('click');
        await wrapper.vm.$nextTick();

        const titleEls = wrapper.findAll('.flex-1.truncate');
        const titles = titleEls.map(el => el.text());

        expect(titles).toEqual(['Task 1', 'Task 2', 'Task 3', 'Task 4']);
    });
});
