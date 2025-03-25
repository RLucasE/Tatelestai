<script setup>
import axios from 'axios';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.baseURL = "http://localhost:8000";

const register = async () => {
    await axios.get("/sanctum/csrf-cookie");
    try {
        const response = await axios.post(
            "/api/register",
            {
                "name": "Lucas",
                "email": "Lucascabjnmro2@gmail.com",
                "password": "12345678",
                "password_confirmation": "12345678"
            });
        console.log(response.data);
    } catch (error) {
        console.log(error);
    }
};

register();
</script>



<template>
    <p>register page</p>
</template>

<style scoped></style>