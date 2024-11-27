import { useEffect } from 'react';

debugger; // Проверка загрузки файла
console.log('ChatForm.tsx loaded');

export function ChatForm() {
  debugger; // Проверка создания компонента
  console.log('ChatForm component created');

  useEffect(() => {
    debugger; // Проверка монтирования
    console.log('ChatForm mounted');
  }, []);

  return (
    <div style={{ border: '2px solid blue', padding: '10px', margin: '10px 0' }}>
      <h3>Chat Form</h3>
      <p>Testing if component renders</p>
    </div>
  );
}
