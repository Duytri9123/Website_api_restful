import axios from "axios";
import router from "./router/index.js"; // Đảm bảo đường dẫn đúng đến router của bạn


// Tạo một instance của axios với cấu hình mặc định
const axiosClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL||"http://127.0.0.1:8000", // Đặt URL cơ sở cho các yêu cầu API

  withCredentials: true, // Cho phép gửi cookie trong các yêu cầu
  withXSRFToken: true, // Thêm CSRF token vào header
});

// axiosClient.interceptors.request.use(
//     (config) => {
//         // Thêm token vào header nếu có
//         const token = localStorage.getItem('token');
//         if (token) {
//             config.headers['Authorization'] = `Bearer ${token}`;
//         }
//         return config;
//     },  (error) => {
//         // Xử lý lỗi ở đây nếu cần
//         return Promise.reject(error);
//     }
// );

axiosClient.interceptors.response.use(
  (response) => {
    // Xử lý phản hồi thành công
    return response;
  },
  (error) => {
    // Xử lý lỗi phản hồi
    if (error.response && error.response.status === 401) {

      router.push({ name: "Login" }); // Chuyển hướng đến trang đăng nhập nếu lỗi 401
      // Nếu lỗi 401, có thể là do token hết hạn hoặc không hợp lệ
    }
    throw error; // Ném lỗi để xử lý ở nơi khác nếu cần
  }
);

export default axiosClient;
