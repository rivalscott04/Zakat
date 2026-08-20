import React from "react";
import { RequireAuth } from "../../features/auth/guards";

const AuthProtected = ({ children }: { children: React.ReactNode }) => <RequireAuth>{children}</RequireAuth>;


export default AuthProtected;
