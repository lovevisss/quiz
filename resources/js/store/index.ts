import axios from 'axios';
import { createStore } from 'vuex';

type AuthUser = {
    name: string;
    user_id: number;
};

type AuthUserResponse = {
    data: null | {
        attributes: AuthUser;
    };
};

export interface UserState {
    user: AuthUser | null;
}

export default createStore({
    state: {
        user: null,
    },
    mutations: {
        setUser(state: UserState, user: AuthUser | null) {
            state.user = user;
        },
    },
    actions: {
        async fetchUser({
            commit,
        }: {
            commit: (type: 'setUser', payload: AuthUser | null) => void;
        }) {
            try {
                const response =
                    await axios.get<AuthUserResponse>('/api/auth/user');
                commit('setUser', response.data.data?.attributes ?? null);
            } catch {
                commit('setUser', null);
            }
        },
    },
    getters: {
        isAuthenticated: (state: UserState) => !!state.user,
        user: (state: UserState) => state.user,
    },
});
