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
          Sign in to your account
        </h2>
      </div>

      <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <form class="space-y-6" @submit.prevent="handleSubmit()">
          <div>
            <label for="email" class="block text-sm/6 font-medium text-gray-900"
              >Email address</label
            >
            <div class="mt-2">
              <input
                type="email"
                name="email"
                id="email"
                autocomplete="email"
                v-model="data.email"
                placeholder="Enter your email"
                @input="clearError('email')"
                required=""
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
              />
            </div>
            <p class="mt-2 text-sm text-red-600" v-if="errors.email?.length">
              <span v-for="(error, index) in errors.email" :key="index">{{ error }}</span>
            </p>
          </div>

          <div>
            <div class="flex items-center justify-between">
              <label for="password" class="block text-sm/6 font-medium text-gray-900"
                >Password</label
              >
              <div class="text-sm">
                <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-500"
                  >Forgot password?</a
                >
              </div>
            </div>
            <div class="mt-2">
              <input
                type="password"
                name="password"
                id="password"
                placeholder="Enter your password"
                @input="clearError('password')"
                autocomplete="current-password"
                v-model="data.password"
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
              />
            </div>
            <p class="mt-2 text-sm text-red-600" v-if="errors.password?.length">
              <span v-for="(error, index) in errors.password" :key="index">{{ error }}</span>
            </p>
          </div>

          <div>
            <button
              type="submit"
              class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Sign in
            </button>
          </div>
        </form>

        <p class="mt-10 text-center text-sm/6 text-gray-500">
          Not a member?
          {{ " " }}
          <router-link
            to="/signup"
            class="font-semibold text-indigo-600 hover:text-indigo-500"
            >Sign up</router-link
          >
        </p>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { initFlowbite } from "flowbite";
import GuestLayout from "../../layout/GuestLayout.vue";
import { ref, onMounted } from "vue";
import axiosClient from "../../axiosClient.js";
import router from "../../router/index.js";

const data = ref({
  email: "",
  password: "",
});
const errors = ref({
  email: [],
  password: [],
});
const clearError = (field) => {
  errors.value[field] = [];
};

const handleSubmit = () => {
  axiosClient.get("/sanctum/csrf-cookie").then(() => {
    axiosClient.post("/login", data.value).then((response) => {
      router.push({ name: "Home" });
    }).catch((error) => {
      console.log(error.response.data);
      errors.value = error.response.data.errors;
    });
  });
};
onMounted(() => {
  initFlowbite();
});
</script>
