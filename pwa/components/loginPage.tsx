import * as React from 'react';
import { Login } from "react-admin"
import authProvider from "./common/authProvider";

const LoginPage = () => (
    <Login>
      <button onClick={authProvider.login}>Login with OIDC</button>
    </Login>
);

export default LoginPage;
