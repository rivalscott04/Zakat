//Include Both Helper File with needed methods
import { getFirebaseBackend } from "../../../shared/helpers/firebase_helper";
import {
  postFakeLogin,
  postJwtLogin,
} from "../../../shared/helpers/fakebackend_helper";

import { setAuthorization } from "../../../shared/helpers/api_helper";
import { loginSuccess, logoutUserSuccess, apiError, reset_login_flag } from "./reducer";

const persistAuthUser = (data: any) => {
  const authUser = {
    ...data,
    token: data.token ?? data.accessToken,
  };

  sessionStorage.setItem("authUser", JSON.stringify(authUser));

  if (authUser.token) {
    setAuthorization(authUser.token);
  }

  return authUser;
};

export const loginUser = (user:any, history:any) => async (dispatch:any) => {

  try {
    let response;
    const authMode = import.meta.env.VITE_DEFAULTAUTH;

    if (authMode === "firebase") {
      let fireBaseBackend :any= getFirebaseBackend();
      response = fireBaseBackend.loginUser(
        user.email,
        user.password
      );
    } else if (authMode === "jwt" || authMode === "fake") {
      response = postJwtLogin({
        email: user.email,
        password: user.password
      });
    } else if (import.meta.env.VITE_API_URL) {
      response = postFakeLogin({
        email: user.email,
        password: user.password,
      });
    } else {
      throw new Error("Auth is not configured");
    }

    var data :any= await response;

    if (data) {
      if (authMode === "fake" && import.meta.env.VITE_API_URL) {
        var finallogin :any= JSON.stringify(data);
        finallogin = JSON.parse(finallogin)
        data = finallogin.data;
        if (finallogin.status === "success") {
          dispatch(loginSuccess(persistAuthUser(data)));
          history('/dashboard')
        } else {
          dispatch(apiError(finallogin));
        }
      } else {
        dispatch(loginSuccess(persistAuthUser(data)));
        history('/dashboard')
      }
    }
  } catch (error) {
    dispatch(apiError(error));
  }
};

export const logoutUser = () => async (dispatch:any) => {
  try {
    sessionStorage.removeItem("authUser");
    let fireBaseBackend :any= getFirebaseBackend();
    if (import.meta.env.VITE_DEFAULTAUTH === "firebase") {
      const response = fireBaseBackend.logout;
      dispatch(logoutUserSuccess(response));
    } else {
      dispatch(logoutUserSuccess(true));
    }

  } catch (error) {
    dispatch(apiError(error));
  }
};

export const socialLogin = (type:any, history:any) => async (dispatch:any) => {
  try {
    let response;

    if (import.meta.env.VITE_DEFAULTAUTH === "firebase") {
      const fireBaseBackend :any= getFirebaseBackend();
      response = fireBaseBackend.socialLoginUser(type);
    }
    //  else {
      //   response = postSocialLogin(data);
      // }
      
      const socialdata = await response;
    if (socialdata) {
      sessionStorage.setItem("authUser", JSON.stringify(response));
      dispatch(loginSuccess(response));
      history('/dashboard')
    }

  } catch (error) {
    dispatch(apiError(error));
  }
};

export const resetLoginFlag = () => async (dispatch:any) => {
  try {
    const response = dispatch(reset_login_flag());
    return response;
  } catch (error) {
    dispatch(apiError(error));
  }
};