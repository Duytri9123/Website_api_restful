import axios from "axios";
import router from "./router/index.js"; // Đảm bảo đường dẫn đúng đến router của bạn

// Tạo một instance của axios với cấu hình mặc định
const axiosAdmin = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000",
  timeout: 43200,
  withCredentials: true,
  withXSRFToken: true,
});
axiosAdmin.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response && error.response.status === 401) {
      router.push({ name: "AdminLogin" });
    }
    throw error;
  }
);

export default axiosAdmin;
