<template>
    <div>
        <h1>Forgot Password</h1>
        <p>Please enter your email address to reset your password.</p>
        <form @submit.prevent="handleSubmit">
            <input type="email" placeholder="Email" v-model="email" required />
            <button type="submit">Send Reset Link</button>
        </form>
    </div>
</template>

<script setup>
import { ref } from "vue";
import axiosClient from "../../../axios.js";

const email = ref("");

const handleSubmit = () => {
    axiosClient.post("/forgot-password", { email: email.value })
        .then(response => {
            console.log("Reset link sent:", response.data);
        })
        .catch(error => {
            console.error("Error sending reset link:", error);
        });
};
</script>

<style lang="scss" scoped>

</style>