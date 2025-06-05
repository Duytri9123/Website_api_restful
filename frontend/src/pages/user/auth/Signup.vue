<template>
  <GuestLayout>
    <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
      <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <img
          class="mx-auto h-10 w-auto"
          src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600"
          alt="Your Company"
        />
        <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">
          Create New Account
        </h2>
      </div>

      <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label for="name" class="block text-sm/6 font-medium text-gray-900"
              >Full Name</label
            >
            <div class="mt-2">
              <input
                v-model="data.name"
                type="text"
                name="name"
                id="name"
                autocomplete="name"
                required=""
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
              />
            </div>
          </div>
          <div>
            <label for="email" class="block text-sm/6 font-medium text-gray-900"
              >Email address</label
            >
            <div class="mt-2">
              <input
                v-model="data.email"
                type="email"
                name="email"
                id="email"
                autocomplete="email"
                required=""
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
              />
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between">
              <label for="password" class="block text-sm/6 font-medium text-gray-900"
                >Password</label
              >
            </div>
            <div class="mt-2">
              <input
                v-model="data.password"
                type="password"
                name="password"
                id="password"
                autocomplete="current-password"
                required=""
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
              />
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between">
              <label
                for="password_confirmation"
                class="block text-sm/6 font-medium text-gray-900"
                >Confirm Password</label
              >
            </div>
            <div class="mt-2">
              <input
                v-model="data.password_confirmation"
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                autocomplete="current-password"
                required=""
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
              />
            </div>
          </div>

          <div>
            <button
              type="submit"
              class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Sign Up
            </button>
          </div>
        </form>

        <p class="mt-10 text-center text-sm/6 text-gray-500">
          Not a member?
          {{ " " }}
          <router-link
            to="/login"
            class="font-semibold text-indigo-600 hover:text-indigo-500"
            >Login from here
          </router-link>
        </p>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { initFlowbite } from "flowbite";
import GuestLayout from "../../../layouts/user/GuestLayout.vue";
import { onMounted, ref } from "vue";
import axiosClient from "../../../axios";
import router from "../../../router/index.js";

const data = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const handleSubmit = () => {
  axiosClient.get("/sanctum/csrf-cookie").then((Response) => {
    axiosClient.post("/register", data.value).then((Response) => {
      router.push("/login");
    });
  });
};
onMounted(() => {
  initFlowbite();
});
</script>
