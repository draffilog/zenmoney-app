import { getZenMoneyProfile } from '@/lib/zenmoney'

export default async function ZenMoneyProfilePage() {
  const profile = await getZenMoneyProfile()

  return (
    <div className="container mx-auto py-8">
      <h1 className="text-2xl font-bold mb-6">Zenmoney Profile</h1>

      <div className="grid gap-4">
        <div className="p-4 border rounded-lg">
          <h2 className="font-semibold mb-2">Основная информация</h2>
          <p>Имя: {profile.name}</p>
          <p>Email: {profile.email}</p>
          <p>Подписка: {profile.subscription}</p>
        </div>

        <div className="p-4 border rounded-lg">
          <h2 className="font-semibold mb-2">Статистика</h2>
          <p>Всего счетов: {profile.accounts?.length}</p>
          <p>Всего транзакций: {profile.transactions?.length}</p>
        </div>
      </div>
    </div>
  )
}
