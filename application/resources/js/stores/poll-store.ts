import {defineStore} from 'pinia';
import {ref, computed, watch, onMounted, Ref} from 'vue';
import {InertiaForm} from "@inertiajs/vue3";
import PollData = App.DataTransferObjects.PollData;
import Pagination from "@/types/pagination";
import PollsQuery from '@/types/polls-query';
import AdminPollService from '@/Pages/Auth/Poll/Services/admin-poll-service';
import PublicPollService from '@/Pages/Poll/services/public-poll-service';
import AlertService from '@/shared/Services/alert-service';

export const usePollStore = defineStore('poll-store', () => {

    let formData = ref<object | null>(null);
    let poll = ref<PollData | null>(null);
    let step = ref<number>(1);
    let pollsData = ref<PollData[]>([]);
    let pollsPagination = ref<Pagination>();
    let pollsQueryData = ref<PollsQuery | null>({p: 1, l: 10});
    let loadingMore = ref(false)
    let publicPoll: Ref<{
        polls: PollData[],
        nextCursor: string,
        hasMorePages: boolean
    }> = ref();

    function uploadFormData(form: any) {
        formData.value = form;
    }

    function uploadPollData(form: InertiaForm<any>) {
        poll.value = form;
    }

    function setStep(newStep: number) {
        step.value = newStep
    }

    function nextStep() {
        step.value = step.value + 1;
    }


    watch(pollsQueryData, () => {
        getAdminPolls(pollsQueryData.value).then();
    })

    async function getAdminPolls(query?: (PollsQuery | null)) {
        await AdminPollService.getPolls(query)
            .then((paginatedResponse) => {
                pollsData.value = paginatedResponse.data;
                pollsPagination.value = paginatedResponse.meta;
            });
    }

    async function loadPublicPolls() {
        try {
            const data = {
                hasMorePages: publicPoll.value?.hasMorePages ?? null,
                nextCursor: publicPoll.value?.nextCursor ?? null
            }

            await PublicPollService.fetchPolls(data)
                .then((res) => {
                    publicPoll.value = res;
                    pollsData.value = [...pollsData.value, ...publicPoll.value.polls];
                }).finally(() => {
                    loadingMore.value = false
                })
        } catch (error) {
            loadingMore.value = false
            AlertService.show(['error'], 'error ')
        }
    }

    const showMore = computed(() => {
        return publicPoll?.value?.nextCursor && publicPoll?.value?.hasMorePages;
    })

    onMounted(() => {
        loadPublicPolls().then()
    });

    return {
        formData,
        poll,
        step,
        pollsData,
        pollsPagination,
        pollsQueryData,
        setStep,
        nextStep,
        uploadFormData,
        uploadPollData,
        loadingMore,
        loadPublicPolls,
        showMore,
        getAdminPolls
    }
});
