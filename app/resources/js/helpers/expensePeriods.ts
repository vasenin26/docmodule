// app/resources/js/helpers/expensePeriods.ts

export type PeriodType = 'day' | 'week' | 'month';

export interface RawApiItem {
  period: string; // raw period from backend (may contain time)
  total_cost?: number;
  task_count?: number;
  period_label?: string;
}

export interface ExpenseSummaryItem {
  period: string; // normalized period start: YYYY-MM-DD
  totalCost: number;
  taskCount: number;
  periodLabel: string;
}

export const MAX_POINTS: Record<PeriodType, number> = {
  day: 365,
  week: 156,
  month: 60,
};

function toYMD(date: Date): string {
  const yyyy = date.getUTCFullYear();
  const mm = String(date.getUTCMonth() + 1).padStart(2, '0');
  const dd = String(date.getUTCDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

function parseDate(input: string | Date): Date {
  if (input instanceof Date) {
    return new Date(Date.UTC(input.getUTCFullYear(), input.getUTCMonth(), input.getUTCDate()));
  }
  const d = new Date(input);
  if (isNaN(d.getTime())) {
    throw new Error(`Invalid date input: ${input}`);
  }
  return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate()));
}

export function normalizePeriodStart(raw: string, period: PeriodType): string {
  const d = parseDate(raw);

  if (period === 'day') {
    return toYMD(d);
  }

  if (period === 'week') {
    const day = d.getUTCDay(); // 0 - Sun, 1 - Mon, ...
    const diffToMonday = (day === 0) ? -6 : (1 - day);
    const monday = new Date(d);
    monday.setUTCDate(d.getUTCDate() + diffToMonday);
    return toYMD(monday);
  }

  // month: return first day of month
  if (period === 'month') {
    const first = new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), 1));
    return toYMD(first);
  }

  return toYMD(d);
}

export function generatePeriodStarts(period: PeriodType, fromYmd: string, toYmd: string): string[] {
  const from = parseDate(fromYmd);
  const to = parseDate(toYmd);

  const result: string[] = [];

  let cursor = new Date(from);

  if (period === 'day') {
    while (cursor <= to) {
      result.push(toYMD(cursor));
      cursor.setUTCDate(cursor.getUTCDate() + 1);
    }
    return result;
  }

  if (period === 'week') {
    const fromDay = cursor.getUTCDay();
    const diffToMonday = (fromDay === 0) ? -6 : (1 - fromDay);
    cursor.setUTCDate(cursor.getUTCDate() + diffToMonday);

    while (cursor <= to) {
      result.push(toYMD(cursor));
      cursor.setUTCDate(cursor.getUTCDate() + 7);
    }
    return result;
  }

  if (period === 'month') {
    cursor = new Date(Date.UTC(cursor.getUTCFullYear(), cursor.getUTCMonth(), 1));
    while (cursor <= to) {
      result.push(toYMD(cursor));
      cursor.setUTCMonth(cursor.getUTCMonth() + 1);
    }
    return result;
  }

  return result;
}

function formatPeriodLabelFromStart(startYmd: string, period: PeriodType): string {
  const d = parseDate(startYmd);
  const day = String(d.getUTCDate()).padStart(2, '0');
  const month = String(d.getUTCMonth() + 1).padStart(2, '0');
  const year = d.getUTCFullYear();

  if (period === 'day') {
    return `${day}.${month}.${year}`;
  }

  if (period === 'week') {
    const end = new Date(d);
    end.setUTCDate(d.getUTCDate() + 6);
    const endDay = String(end.getUTCDate()).padStart(2, '0');
    const endMonth = String(end.getUTCMonth() + 1).padStart(2, '0');
    const endYear = end.getUTCFullYear();
    return `${day}.${month}.${year} - ${endDay}.${endMonth}.${endYear}`;
  }

  return d.toLocaleString('default', { month: 'long', year: 'numeric' });
}

function mapApiItem(apiItem: RawApiItem, period: PeriodType): { start: string; item: ExpenseSummaryItem } {
  const start = normalizePeriodStart(apiItem.period, period);
  const totalCost = (typeof apiItem.total_cost === 'number') ? apiItem.total_cost : 0;
  const taskCount = (typeof apiItem.task_count === 'number') ? apiItem.task_count : 0;
  const periodLabel = apiItem.period_label || formatPeriodLabelFromStart(start, period);

  return {
    start,
    item: {
      period: start,
      totalCost,
      taskCount,
      periodLabel,
    },
  };
}

export function mergeApiDataWithGenerated(
  period: PeriodType,
  from: string,
  to: string,
  apiData: RawApiItem[],
  maxPointsOverride?: number,
): ExpenseSummaryItem[] {
  const generated = generatePeriodStarts(period, from, to);

  const limit = typeof maxPointsOverride === 'number' ? maxPointsOverride : MAX_POINTS[period];

  if (generated.length > limit) {
    console.warn(`ExpenseSummary: requested ${generated.length} points for period='${period}', limiting to ${limit} points starting from ${generated[0]}`);
    generated.splice(limit);
  }

  const map = new Map<string, ExpenseSummaryItem>();
  for (const raw of apiData) {
    try {
      const { start, item } = mapApiItem(raw, period);
      if (map.has(start)) {
        const prev = map.get(start)!;
        prev.totalCost += item.totalCost;
        prev.taskCount += item.taskCount;
      } else {
        map.set(start, item);
      }
    } catch (e) {
      console.warn('ExpenseSummary: failed to parse api item period', raw, e);
    }
  }

  const result: ExpenseSummaryItem[] = generated.map(start => {
    const existing = map.get(start);
    if (existing) return existing;

    return {
      period: start,
      totalCost: 0,
      taskCount: 0,
      periodLabel: formatPeriodLabelFromStart(start, period),
    };
  });

  result.sort((a, b) => (a.period < b.period ? -1 : a.period > b.period ? 1 : 0));

  return result;
}
