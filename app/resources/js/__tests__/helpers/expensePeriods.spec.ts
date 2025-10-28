// app/resources/js/__tests__/helpers/expensePeriods.spec.ts
import { mergeApiDataWithGenerated, MAX_POINTS } from '@/helpers/expensePeriods';

describe('expensePeriods helper', () => {
  test('generates missing day periods', () => {
    const api = [
      { period: '2023-10-02', total_cost: 100, task_count: 1, period_label: '02.10.2023' }
    ];

    const res = mergeApiDataWithGenerated('day', '2023-10-01', '2023-10-03', api as any);
    expect(res).toHaveLength(3);
    expect(res[0].period).toBe('2023-10-01');
    expect(res[0].totalCost).toBe(0);
    expect(res[1].period).toBe('2023-10-02');
    expect(res[1].totalCost).toBe(100);
  });

  test('generates missing week periods (week starts Monday)', () => {
    // 2023-10-02 is Monday
    const api = [
      { period: '2023-10-02', total_cost: 200, task_count: 2, period_label: '02.10.2023 - 08.10.2023' }
    ];

    const res = mergeApiDataWithGenerated('week', '2023-10-02', '2023-10-16', api as any);
    // weeks: 2023-10-02, 2023-10-09, 2023-10-16
    expect(res.length).toBe(3);
    expect(res[0].period).toBe('2023-10-02');
    expect(res[1].period).toBe('2023-10-09');
    expect(res[2].period).toBe('2023-10-16');
  });

  test('respects max points and truncates', () => {
    const from = '2020-01-01';
    const to = '2025-01-01';
    const api: any[] = [];
    const res = mergeApiDataWithGenerated('month', from, to, api);
    expect(res.length).toBeLessThanOrEqual(MAX_POINTS.month);
  });
});
