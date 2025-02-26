import { createApp } from 'vue'
import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'
import Toast from "vue-toastification"
import "vue-toastification/dist/index.css"
import { CkeditorPlugin } from '@ckeditor/ckeditor5-vue'
import './bootstrap' 

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'

// Create vue app
const app = createApp(App)

const options = {
  position: "top-right",
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: false,
  hideProgressBar: false,
  closeButton: "button",
  icon: true,
  rtl: false,
}

// Register plugins
registerPlugins(app)

app.use(Toast, options)
app.use( CkeditorPlugin )

// Mount vue app
app.mount('#app')
