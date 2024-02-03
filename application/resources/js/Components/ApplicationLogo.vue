<template>
    <img :src="configStore.isDarkMode ? darkLogo : lightLogo" class="h-10">
</template>
<script setup>
import lightLogo from "@/images/logo-light.svg";
import darkLogo from "@/images/logo-dark.svg";
import {useConfigStore} from "@/stores/config-store";
import { storeToRefs } from 'pinia';

let configStore = useConfigStore();
let { config } = storeToRefs(configStore);
</script>
