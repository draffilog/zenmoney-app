interface AdminSettings {
  zenMoneyToken: string;
}

export async function getAdminSettings(): Promise<AdminSettings> {
  try {
    const response = await fetch('/admin/settings/token');

    if (!response.ok) {
      throw new Error('Failed to fetch admin settings');
    }

    const data = await response.json();
    console.log('Settings response:', data);
    return data;
  } catch (error) {
    console.error('Error fetching admin settings:', error);
    throw error;
  }
}
