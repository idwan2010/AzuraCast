import {Router} from "vue-router";
import {App} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import NProgress from 'nprogress';

export function installRouter(router: Router, vueApp: App): void {
    // Add remote prop loading support
    router.beforeEach(async (to, from, next) => {
        if (to.meta.remoteUrl) {
            const {axios} = useAxios();
            to.meta.state = await axios.get(to.meta.remoteUrl).then(r => r.data);
        }
        next();
    });

    // Add NProgress displays
    router.beforeResolve((to, from, next) => {
        if (to.name) {
            NProgress.start();
        }
        next();
    });

    router.afterEach(() => {
        NProgress.done();
    });

    vueApp.use(router);
}
