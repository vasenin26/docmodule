import { Method, type ApiInterface, type Request } from '@/services/api/Api';
import type { ExpenseSummaryFilters, ExpenseSummaryResponse } from '@/types';

export class ExpenseSummaryDataRequest implements Request<ExpenseSummaryResponse> {
  public readonly method: Method;
  public readonly url: string;
  public readonly body: any;

  constructor(filters: ExpenseSummaryFilters) {
    this.method = Method.GET;
    this.body = null;

    // Формируем URL с параметрами сразу в конструкторе
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      // explicitly allow model as nullable string
      if (value !== null && value !== '') {
        params.append(key, value.toString());
      }
    });

    this.url = `/expense-summary/data?${params.toString()}`;
  }

  async call(api: ApiInterface): Promise<ExpenseSummaryResponse> {
    return api.execute<ExpenseSummaryResponse>(this);
  }
}
