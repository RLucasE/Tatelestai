<template>
  <nav class="flex justify-between items-center bg-gray-500 pl-6 pr-6 h-16">
    <div>
      <a
        href="#"
        class="flex-no-grow flex-no-shrink relative py-2 px-4 leading-normal text-white no-underline flex items-center hover:bg-gray-700"
        >Logotipo</a
      >
    </div>
    <div>
      <a href="#" class="py-2 px-4 text-white no-underline hover:bg-gray-700"
        >Carrito</a
      >
      <a
        href="#"
        class="py-2 px-4 leading-normal text-white no-underline hover:bg-gray-700"
        >Perfil</a
      >
    </div>
  </nav>
  <main>
    <RouterView></RouterView>
    <RouterView></RouterView>
    <RouterView></RouterView>
  </main>
</template>
