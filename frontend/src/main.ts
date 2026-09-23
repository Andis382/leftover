import '@fontsource-variable/fraunces/full.css'
import '@fontsource-variable/figtree'
import './styles/tokens.css'
import './styles/base.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { i18n } from './i18n'
import { refreshCsrf, setUnauthorizedHandler } from './lib/api'
import { useAuth } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(i18n)
app.use(router)

// A 401 anywhere means the session ended (expired, signed out on another device).
setUnauthorizedHandler(() => {
  const auth = useAuth(pinia)
  if (auth.signedIn) {
    auth.clear()
    const here = router.currentRoute.value
    if (here.meta.auth || here.matched.some((r) => r.meta.auth)) {
      router.replace({ name: 'login', query: { next: here.fullPath } })
    }
  }
})

refreshCsrf().finally(() => app.mount('#app'))
