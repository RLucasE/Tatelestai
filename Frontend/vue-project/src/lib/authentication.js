import axiosInstance from "./axios";

const baseURL = "http://localhost:8000";

export const loginLogic = async (data) => {
  await axiosInstance.get("/sanctum/csrf-cookie", {
    baseURL,
  });
  try {
    const response = await axiosInstance.post("/login", data);
    console.log(response.data);
  } catch (error) {
    console.error("loginLogicError in authentication: ", error);
    throw error;
  }
};

export const registerLogic = async (data) => {
  await axiosInstance.get("/sanctum/csrf-cookie", {
    baseURL,
  });
  try {
    await axiosInstance.post("/register", data);
  } catch (e) {
    console.error("registerLogic error in authentication", e);
    throw e;
  }
};
