const decodeJwtPayload = (token: string): Record<string, unknown> | null => {
  try {
    const parts = token.split('.');
    if (parts.length < 2) {
      return null;
    }
    let payload = parts[1].replace(/-/g, '+').replace(/_/g, '/');
    while (payload.length % 4 !== 0) {
      payload += '=';
    }
    const json = atob(payload);
    const decoded = JSON.parse(json);
    if (decoded === null || typeof decoded !== 'object') {
      return null;
    }
    return decoded as Record<string, unknown>;
  } catch {
    return null;
  }
};
export default (tokenJson: string | null) => {
  if (!tokenJson) {
    return null;
  }
  let token: any;
  try {
    token = JSON.parse(tokenJson);
  } catch {
    return null;
  }
  if (!token || typeof token !== 'object' || typeof token.id_token !== 'string') {
    return null;
  }
  const jwt = decodeJwtPayload(token.id_token);
  if (!jwt) {
    return null;
  }
  return { id: 'my-profile', ...jwt };
}
