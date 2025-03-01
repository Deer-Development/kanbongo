import { createApp } from 'vue'
import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'
import Toast from "vue-toastification"
// Import the CSS but we'll override styles with our custom SCSS
import "vue-toastification/dist/index.css"
import { CkeditorPlugin } from '@ckeditor/ckeditor5-vue'
import './bootstrap' 

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'

// Create vue app
const app = createApp(App)

// GitHub-style toast configuration
const toastOptions = {
  position: "top-right",
  timeout: 4000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: true,
  hideProgressBar: false,
  closeButton: "button",
  icon: true,
  rtl: false,
  transition: "Vue-Toastification__bounce",
  maxToasts: 3,
  toastClassName: "Vue-Toastification__toast--animate-icon",
  bodyClassName: ["Vue-Toastification__toast-body"]
}

// Register plugins
registerPlugins(app)

app.use(Toast, toastOptions)
app.use(CkeditorPlugin)

// Mount vue app
app.mount('#app')
