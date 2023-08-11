import axios from "axios";

export default class VoterService {
    static async loadVotingPower(voterId: string, ballotHash: string) {
        try {
            let response = await axios.get(
                route("voters.power", { voterId, ballot: ballotHash })
            );
            return response.data;
        } catch (error) {
            console.log(error);
        }
    }

    static async saveBallotResponse(
        voterId: string,
        payload: { choice_hash: string; ballot_hash: string }
    ) {
        try {
            let response = await axios.post(
                route("voters.ballot-responses.save", { voterId }),
                payload
            );
            return response.data;
        } catch (error) {
            console.log(error);
        }
    }

    static async submitVote(voterId:string,data:{ballot_id:string|null,choices:number}) {
        try {
            let response = await axios.post(
                route("voters.submitVote", { voterId }),
                data
            );
            return response.data;
        } catch (error) {
            console.log(error);
        }
    }
}
