import { getAdminSettings } from './settingsService';

export async function getZenMoneyToken(): Promise<string> {
  const settings = await getAdminSettings();

  if (!settings.zenMoneyToken) {
    throw new Error('ZenMoney API token not found in admin settings');
  }

  return settings.zenMoneyToken;
}
