import {defineStore} from 'pinia';
import { ref, watch} from 'vue';
import SnapshotService from '@/Pages/Auth/Snapshot/Services/SnapshotService';
import VotingPowerData = App.DataTransferObjects.VotingPowerData;
import Pagination from "@/types/pagination";
import votingPowerQuery from "@/types/voting-power-query";

export const useSnapshotStore = defineStore('snapshot-store', () => {
    let snapshotHash = ref('');
    let votingPowersData = ref<VotingPowerData[]>([]);
    let votingPowersPagination = ref<Pagination>();
    let queryData = ref<votingPowerQuery|null>({p:1, l:40, st:''})

    async function loadVotingPowers(snapHash: string) {
        snapshotHash.value = snapHash;
        getVotingPower(snapshotHash.value);
    }

    watch(queryData, () => {
        getVotingPower(snapshotHash.value, queryData.value);
    })

    async function getVotingPower(snapshotHash:string, query?: (votingPowerQuery|null)) {
        await SnapshotService.getSnapshotVotingPowers(snapshotHash, query)
        .then((paginatedResponse) => {
            votingPowersData.value = paginatedResponse.data;
            votingPowersPagination.value = paginatedResponse.meta;
        });
    }

    return {
        snapshotHash,
        queryData,
        votingPowersData,
        votingPowersPagination,
        loadVotingPowers,
    }
});
