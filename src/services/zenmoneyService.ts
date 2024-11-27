import { ZenMoneyTag } from '../types/zenmoney';

export class ZenMoneyService {
  private apiUrl = 'https://api.zenmoney.ru/v8/diff/';

  async getTags(token: string): Promise<ZenMoneyTag[]> {
    try {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          currentClientTimestamp: Math.floor(Date.now() / 1000),
          tag: {
            currentClientTimestamp: 0
          }
        })
      });

      if (!response.ok) {
        throw new Error('Failed to fetch ZenMoney categories');
      }

      const data = await response.json();
      return data.tag || [];

    } catch (error) {
      console.error('Error fetching ZenMoney categories:', error);
      throw error;
    }
  }
}
