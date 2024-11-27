const ZENMONEY_API_URL = 'https://api.zenmoney.ru/v8/diff'

export async function getZenMoneyProfile() {
  const response = await fetch(ZENMONEY_API_URL + '/profile', {
    headers: {
      'Authorization': `Bearer ${process.env.ZENMONEY_TOKEN}`,
      'Content-Type': 'application/json',
    },
  })

  if (!response.ok) {
    throw new Error('Failed to fetch Zenmoney profile')
  }

  return response.json()
}
