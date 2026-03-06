import {fetchUtils} from "react-admin";
import {openApiDataProvider} from "@api-platform/admin";
import simpleRestProvider from "ra-data-simple-rest";

const getAccessToken = () => {
  const token = localStorage.getItem("token");
  if (!token) {
    return null;
  }

  // JSON decode the token to get the access_token property
  const jwt = JSON.parse(token);
  return jwt.access_token;
}

const httpClient = async (url: string, options: fetchUtils.Options = {}) => {
  options.headers = new Headers({
    ...options.headers,
    Accept: 'application/json',
  }) as Headers;

  const token = getAccessToken();
  options.user = { token: `Bearer ${token}`, authenticated: !!token };

  return await fetchUtils.fetchJson(url, options);
};

const dataProvider = openApiDataProvider({
  dataProvider: simpleRestProvider(window.origin.toString(), httpClient),
  entrypoint: window.origin.toString(),
  docEntrypoint: `docs.jsonopenapi`,
});

export default dataProvider;
