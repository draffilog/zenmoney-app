import { Head } from '@inertiajs/react';
import { PageProps } from '@/types';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ChatForm } from '@/components/ChatForm';

debugger;
console.log('Create.tsx loaded');

export default function Create({ auth }: PageProps) {
  debugger;
  console.log('Create component rendering');

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Create Chat</h2>}
    >
      <Head title="Create Chat" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900">
              <div style={{ border: '1px solid red' }}>Before ChatForm</div>
              <ChatForm />
              <div style={{ border: '1px solid red' }}>After ChatForm</div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
