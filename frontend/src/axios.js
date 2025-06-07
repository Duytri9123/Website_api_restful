import axios from "axios";
import router from "./router/index.js"; // Đảm bảo đường dẫn đúng đến router của bạn


// Tạo một instance của axios với cấu hình mặc định
const axiosClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL||'http://127.0.0.1:8000',
  withCredentials: true,
  withXSRFToken: true
})

// axiosClient.interceptors.request.use(config => {
//   config.headers.Authorization = `Bearer ${localStorage.getItem('token')}`
// })

axiosClient.interceptors.response.use((response) => {
  return response;
}, error => {
  if (error.response && error.response.status === 401) {
    router.push({name: 'Login'});
  }

  throw error;
})

export default axiosClient