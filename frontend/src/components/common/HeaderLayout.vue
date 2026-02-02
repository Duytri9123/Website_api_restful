<script setup>
import axios from "axios";
import { initFlowbite } from "flowbite";
import { ref, onMounted, onBeforeUnmount, computed, nextTick } from "vue";
import axiosClient from "../../axiosClient.js";
import router from "../../router/index.js";
import { useUserStore } from "../../stores/user.js";
import { useCartStore } from "../../stores/cart.js";
import { useFavoriteStore } from "../../stores/favorite.js";
// Navigation links

const isLoggedIn = ref(false);
const userDropdownOpen = ref(false);
const mobileMenuOpen = ref(false);
const mobileSearchInputOpen = ref(false);
const searchQuery = ref("");
const mobileUserDropdownOpen = ref(false);

const navigation = [
  { name: "Home", to: { name: "/" } },
  { name: "About", to: { name: "About" } },
  { name: "Contact", to: { name: "Contact" } },
];

function logout() {
  axiosClient.post("/logout").then(() => {
    router.push({ name: "Login" });
  });
}

const checkLoginStatus = () => {
  isLoggedIn.value = user.value && user.value.id !== undefined && user.value.id !== null;
};
// user store
const userStore = useUserStore();
const user = computed(() => userStore.user);
// cart store
const cartStore = useCartStore();
// use cart count from store
const cartItemCount = computed(() => cartStore.count);
const notificationCount = ref(0);

const favoriteStore = useFavoriteStore();
favoriteStore.loadFromLocalStorage();

//
const mobileSearchInputRef = ref(null);
const userMenuButtonRef = ref(null);
const userDropdownRef = ref(null);
const mobileMenuToggleRef = ref(null);
const mobileMenuContentRef = ref(null);
const mobileSearchToggleRef = ref(null);
const mobileSearchFormRef = ref(null);

const mobileUserDropdownToggleRef = ref(null);

const hasNotifications = computed(() => notificationCount.value >= 0);
const hasCartItems = computed(() => cartItemCount.value > 0);

const favoriteCount = computed(() => favoriteStore.favoriteCount);
const hasFavorites = computed(() => favoriteCount.value > 0);

const toggleUserDropdown = () => {
  userDropdownOpen.value = !userDropdownOpen.value;
  mobileMenuOpen.value = false;
  mobileSearchInputOpen.value = false;
  mobileUserDropdownOpen.value = false;
};

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
  mobileSearchInputOpen.value = false;
  userDropdownOpen.value = false;
  mobileUserDropdownOpen.value = false;
};

const toggleSearchInput = () => {
  mobileSearchInputOpen.value = !mobileSearchInputOpen.value;
  mobileMenuOpen.value = false;
  userDropdownOpen.value = false;
  mobileUserDropdownOpen.value = false; // Close mobile user dropdown if open
  if (mobileSearchInputOpen.value) {
    nextTick(() => {
      mobileSearchInputRef.value?.focus();
    });
  }
};

const toggleMobileUserDropdown = () => {
  mobileUserDropdownOpen.value = !mobileUserDropdownOpen.value;
};

const performSearch = () => {
  if (searchQuery.value.trim() !== "") {
    console.log("Performing search for:", searchQuery.value);
  } else {
    console.log("Search query is empty.");
  }
  mobileSearchInputOpen.value = false;
};

const handleClickOutside = (event) => {
  if (
    userDropdownOpen.value &&
    userMenuButtonRef.value &&
    !userMenuButtonRef.value.contains(event.target) &&
    userDropdownRef.value &&
    !userDropdownRef.value.contains(event.target)
  ) {
    userDropdownOpen.value = false;
  }

  if (
    mobileMenuOpen.value &&
    mobileMenuToggleRef.value &&
    !mobileMenuToggleRef.value.contains(event.target) &&
    mobileMenuContentRef.value &&
    !mobileMenuContentRef.value.contains(event.target)
  ) {
    mobileMenuOpen.value = false;
    mobileUserDropdownOpen.value = false;
  }

  if (
    mobileUserDropdownOpen.value &&
    mobileMenuContentRef.value && // Check if mobile menu content exists
    mobileMenuContentRef.value.contains(event.target) && // Ensure click is within mobile menu
    event.target.closest('button[aria-label="Open mobile user menu"]') !==
      mobileUserDropdownToggleRef.value && // Exclude the toggle button itself
    !event.target.closest(".w-full.mt-2") // Exclude the dropdown content itself
  ) {
  }

  if (
    mobileSearchInputOpen.value &&
    mobileSearchToggleRef.value &&
    !mobileSearchToggleRef.value.contains(event.target) &&
    mobileSearchFormRef.value &&
    !mobileSearchFormRef.value.contains(event.target)
  ) {
    mobileSearchInputOpen.value = false;
  }
};

onMounted(() => {
  initFlowbite();
  checkLoginStatus();
  cartStore.loadFromLocalStorage();

  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>
<template>
  <header
    class="sticky top-0 z-50 bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700 shadow-sm"
  >
    <nav
      class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4 lg:flex-nowrap"
      aria-label="Main navigation"
    >
      <!-- Desktop user dropdown -->
      <div class="w-1/2 flex items-center lg:order-1 space-x-16 rtl:space-x-reverse">
        <!-- logo -->
        <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
          <img
            src="https://flowbite.com/docs/images/logo.svg"
            class="h-8"
            alt="Flowbite Logo"
          />
          <span
            class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white"
            >Duytris</span
          >
        </a>
        <!-- navigation -->
        <div class="hidden lg:flex lg:w-auto lg:order-1" id="navbar-main-links">
          <ul
            class="flex flex-col font-medium p-0 mt-0 lg:space-x-8 rtl:space-x-reverse lg:flex-row lg:border-0 lg:bg-white dark:bg-gray-900"
          >
            <li v-for="item in navigation" :key="item.name">
              <router-link
                :to="item.to.name"
                :class="[
                  $route.name === item.name
                    ? 'border-b-2 border-blue-700 bg-blue-700 block py-2 px-3 text-white rounded-sm lg:bg-transparent lg:text-blue-700 lg:p-0 dark:text-white lg:dark:text-blue-500'
                    : '',
                ]"
                aria-current="page"
                >{{ item.name }}</router-link
              >
            </li>
          </ul>
        </div>
      </div>
      <!-- Search and notifications -->
      <div class="flex items-center mr-5 lg:order-2 space-x-2 rtl:space-x-reverse">
        <!-- Search form -->
        <form @submit.prevent="performSearch" class="relative hidden lg:block">
          <div
            class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none"
          >
            <svg
              class="w-4 h-4 text-gray-500 dark:text-gray-400"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 20 20"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
              ></path>
            </svg>
          </div>
          <!-- Search input -->
          <input
            type="text"
            v-model="searchQuery"
            id="search-navbar"
            class="block w-full pr-10 p-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            placeholder="Search..."
          />
          <!-- Search button -->
          <button
            type="submit"
            class="absolute inset-y-0 end-0 flex items-center pe-3 text-sm font-medium text-blue-700 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-600 focus:outline-none"
            aria-label="Search"
          >
            Search
          </button>
        </form>
        <!-- Mobile search toggle -->
        <button
          type="button"
          @click="toggleSearchInput"
          ref="mobileSearchToggleRef"
          class="lg:hidden text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-500 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700"
          aria-label="Search"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            ></path>
          </svg>
          <span class="sr-only">Open search</span>
        </button>
        <!-- Notifications -->
        <button
          v-if="isLoggedIn"
          class="hidden lg:block text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-500 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700 relative"
          aria-label="Notifications"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"
            ></path>
          </svg>
          <span
            v-if="hasNotifications"
            class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-100 bg-blue-600 rounded-full"
            >{{ notificationCount }}</span
          >
          <span class="sr-only">Unread notifications</span>
        </button>
        <!-- Favorites -->
        <router-link
          to="/favorites"
          class="hidden lg:block text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-500 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700"
          aria-label="Favorites"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            ></path>
          </svg>
          <span
            v-if="hasFavorites"
            class="absolute top-3 ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-pink-100 bg-pink-600 rounded-full"
          >
            {{ favoriteCount }}
          </span>
          <span class="sr-only">Favorites</span>
        </router-link>

        <!-- Shopping Cart -->
        <router-link
          to="/cart"
          class="hidden mr-5 lg:block text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-500 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700 relative"
          aria-label="Shopping Cart"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
            ></path>
          </svg>
          <!-- Cart item count -->
          <span
            v-if="hasCartItems"
            class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"
            >{{ cartItemCount }}</span
          >
          <span class="sr-only">Items in cart</span>
        </router-link>
        <!-- User menu -->
        <div v-if="isLoggedIn" class="relative hidden lg:block">
          <!-- User information -->
          <button
            @click="toggleUserDropdown"
            type="button"
            ref="userMenuButtonRef"
            class="flex text-sm bg-gray-800 rounded-full lg:me-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
            id="user-menu-button"
            :aria-expanded="userDropdownOpen"
            data-dropdown-placement="bottom"
            aria-label="Open user menu"
            aria-haspopup="true"
          >
            <!-- User avatar -->
            <span class="sr-only">Open user menu</span>
            <img
              class="w-8 h-8 rounded-full"
              src="https://flowbite.com/docs/images/people/profile-picture-3.jpg"
              alt="User photo"
            />
          </button>
          <!-- User dropdown menu -->
          <div
            v-if="userDropdownOpen"
            ref="userDropdownRef"
            class="absolute right-0 mt-2 w-48 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow-sm dark:bg-gray-700 dark:divide-gray-600 z-50"
          >
            <div class="px-4 py-3">
              <span class="block text-sm text-gray-900 dark:text-white">{{
                user.name
              }}</span>
              <span class="block text-sm text-gray-500 truncate dark:text-gray-400">{{
                user.email
              }}</span>
            </div>
            <!-- User navigation -->
            <ul class="py-2" aria-labelledby="user-menu-button">
              <li>
                <a
                  href="#"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                  >Dashboard</a
                >
              </li>
              <li>
                <a
                  href="#"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                  >Settings</a
                >
              </li>
              <li>
                <a
                  href="#"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                  >Earnings</a
                >
              </li>
              <li>
                <a
                  href="#"
                  @click.prevent="logout"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                  >Sign out</a
                >
              </li>
            </ul>
          </div>
        </div>
        <!-- Sign Up / Login buttons -->
        <div v-else class="space-x-2 hidden lg:flex">
          <router-link
            to="/signup"
            class="text-gray-800 dark:text-white hover:bg-gray-50 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-3 py-1.5 lg:px-4 lg:py-2 dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800"
            >Sign Up</router-link
          >
          <router-link
            to="/login"
            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-3 py-1.5 lg:px-4 lg:py-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
            >Login</router-link
          >
        </div>
        <!-- Mobile menu toggle -->
        <button
          @click="toggleMobileMenu"
          type="button"
          ref="mobileMenuToggleRef"
          class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
          aria-controls="navbar-mobile-content"
          :aria-expanded="mobileMenuOpen"
          aria-label="Open main menu"
        >
          <span class="sr-only">Open main menu</span>
          <svg
            class="w-5 h-5"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 17 14"
          >
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M1 1h15M1 7h15M1 13h15"
            />
          </svg>
        </button>
      </div>
      <!-- Mobile search form -->
      <form
        @submit.prevent="performSearch"
        v-if="mobileSearchInputOpen"
        ref="mobileSearchFormRef"
        class="w-full relative lg:hidden mt-4 "
      >
        <div
          class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none"
        >
          <svg
            class="w-4 h-4 text-gray-500 dark:text-gray-400"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 20 20"
          >
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
            />
          </svg>
        </div>
        <input
          type="text"
          v-model="searchQuery"
          ref="mobileSearchInputRef"
          id="mobile-search-input"
          class="block w-full p-2 ps-10 pe-20 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          placeholder="Search..."
        />
        <button
          type="submit"
          class="absolute inset-y-0 end-0 flex items-center pe-3 text-sm font-medium text-blue-700 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-600 focus:outline-none"
          aria-label="Perform search"
        >
          Search
        </button>
      </form>
      <!-- Mobile user menu -->
      <div
        v-if="mobileMenuOpen"
        ref="mobileMenuContentRef"
        class="w-full lg:hidden flex flex-col items-center mt-4 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700 p-4"
        id="navbar-mobile-content"
      >
        <div class="w-full mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
          <div v-if="isLoggedIn" class="flex flex-col items-start py-2">
            <button
              @click="toggleMobileUserDropdown"
              type="button"
              class="flex items-center space-x-3 rtl:space-x-reverse py-2 w-full text-left"
              :aria-expanded="mobileUserDropdownOpen"
              aria-label="Open mobile user menu"
            >
              <img
                class="w-8 h-8 rounded-full"
                src="https://flowbite.com/docs/images/people/profile-picture-3.jpg"
                alt="User photo"
              />
              <span class="block text-sm text-gray-900 dark:text-white">{{
                user.name
              }}</span>
              <svg
                class="w-3 h-3 ms-auto transform transition-transform duration-200"
                :class="{ 'rotate-180': mobileUserDropdownOpen }"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 10 6"
              >
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="m1 1 4 4 4-4"
                />
              </svg>
            </button>
            <!-- User information -->
            <div v-if="mobileUserDropdownOpen" class="w-full mt-2">
              <div class="px-4 py-3">
                <span class="block text-sm text-gray-500 truncate dark:text-gray-400">{{
                  user.email
                }}</span>
              </div>
              <ul class="py-2">
                <li>
                  <a
                    href="#"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                    >Dashboard</a
                  >
                </li>
                <li>
                  <a
                    href="#"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                    >Settings</a
                  >
                </li>
                <li>
                  <a
                    href="#"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                    >Earnings</a
                  >
                </li>
              </ul>
            </div>
          </div>
          <!-- User actions -->
          <div v-else class="flex flex-col space-y-2">
            <router-link
              to="/signup"
              class="text-gray-800 dark:text-white bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center dark:bg-gray-700 dark:hover:bg-gray-600 focus:outline-none dark:focus:ring-gray-800"
              >Sign Up</router-link
            >
            <router-link
              to="/login"
              class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
              >Login</router-link
            >
          </div>
        </div>
        <div
          class="w-full flex justify-around p-2 border-b border-gray-200 dark:border-gray-700 mb-4 pb-4"
        >
          <button
            v-if="isLoggedIn"
            type="button"
            class="flex flex-col items-center text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-500 p-2 rounded-full relative"
            aria-label="Notifications"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 0 11-6 0v-1m6 0H9"
              ></path>
            </svg>
            <span class="text-xs">Notifications</span>
            <span
              v-if="hasNotifications"
              class="absolute -top-1 right-3 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-100 bg-blue-600 rounded-full"
              >{{ notificationCount }}</span
            >
          </button>
          <!-- Favorites -->
          <router-link
            to="/favorites"
            class="flex flex-col items-center text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-500 p-2 rounded-full"
            aria-label="Favorites"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
              ></path>
            </svg>
            <span class="text-xs">Favorites</span>
          </router-link>
          <!-- Shopping Cart -->
          <router-link
            to="/cart"
            class="flex flex-col items-center text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-500 p-2 rounded-full relative"
            aria-label="Shopping Cart"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
              ></path>
            </svg>
            <span class="text-xs">Cart</span>
            <span
              v-if="hasCartItems"
              class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"
              >{{ cartItemCount }}</span
            >
          </router-link>
        </div>
        <!-- Navigation -->
        <ul class="flex flex-col w-full font-medium pt-4 space-y-2">
          <li v-for="item in navigation" :key="item.name">
            <router-link
              :to="item.to.name"
              :class="[
                $route.name === item.name
                  ? 'block py-2 px-3 text-white bg-blue-700 rounded-sm dark:bg-blue-600 dark:text-white'
                  : 'block py-2 px-3 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600',
              ]"
            >
              {{ item.name }}
            </router-link>
          </li>
          <li v-if="isLoggedIn">
            <a
              href="#"
              @click.prevent="logout"
              class="block py-2 px-3 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
              >Sign out</a
            >
          </li>
        </ul>
      </div>
    </nav>
  </header>
</template>

<style scoped></style>
