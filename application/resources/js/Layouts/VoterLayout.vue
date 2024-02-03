<script setup lang="ts">
import GlobalAlertComponent from '../shared/components/GlobalAlertComponent.vue';
import {Head} from '@inertiajs/vue3';
import Header from "@/Pages/Partials/Header.vue";
import Footer from "@/Pages/Partials/Footer.vue";
import {useConfigStore} from "@/stores/config-store";
import {storeToRefs} from 'pinia';
import {Modal} from 'momentum-modal';
import light_bg from "@/images/page-bg.png"
import dark_bg from "@/images/page-bg-dark.png"

withDefaults(
    defineProps<{
        page: string;
        pageData?: any;
        canLogin?: boolean;
    }>(), {
        canLogin: true
    });

let configStore = useConfigStore();
let {isDarkMode} = storeToRefs(configStore);

const bg_img = {
    light: 'url(' + light_bg + ') no-repeat center center',
    dark: 'url(' + dark_bg + ') no-repeat center center'
}
</script>
<template>
    <div class="h-full overflow-y-auto" :class="{'dark': isDarkMode }">
        <div class="min-h-screen bg-white dark:bg-gray-900 h-full">
            <Head :title="page"/>
            <div
                class="body-wrap relative flex flex-col justify-start min-h-screen bg-center bg-dots dark:bg-gray-900 selection:bg-red-500 selection:text-white">
                <div class="relative z-50 w-full p-4 text-right border-b">
                    <div class="container">
                        <Header :can-login="canLogin" :pageData="pageData"/>
                    </div>
                </div>
                <header class="bg-white shadow-xs border-b dark:bg-gray-800">
                    <div class="container">
                        <slot name="header"/>
                    </div>
                </header>
                <main class="z-10 flex flex-1">
                    <slot/>
                </main>
                <div class="relative z-50 w-full text-right border-t border-slate-300 mt-auto">
                    <Footer :pageData="pageData"/>
                </div>
            </div>
            <div class="fixed top-0 left-0 z-50 flex items-end justify-end w-full h-full pointer-events-none">
                <GlobalAlertComponent/>
            </div>
            <Modal/>
        </div>
    </div>
</template>
<style scoped>
.bg-dots {
    background: v-bind('bg_img.light');
    background-size: contain;
}

.dark .bg-dots {
    background: v-bind('bg_img.dark')
}

</style>
