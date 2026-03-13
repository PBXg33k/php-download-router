import { useEffect, useState } from "react";
import { useRouter } from "next/router";
import authProvider from "../../components/common/authProvider";

const AuthCallback = () => {
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Only run when the router is ready and query params are available
    if (!router.isReady) return;

    const { code, state } = router.query;
    if (!code || !state) {
      setError("Missing authentication parameters.");
      return;
    }

    if(authProvider && typeof authProvider.handleCallback === "function") {
      authProvider
        .handleCallback()
        .then(() => {
          router.replace("/admin");
        })
        .catch(() => {
          setError("Authentication failed. Please try again.");
        });
    } else {
      setError("Authentication provider is not available.");
    }
  }, [router.isReady, router.query, router]);

  if (error) {
    return (
      <div style={{ display: "flex", justifyContent: "center", alignItems: "center", height: "100vh" }}>
        <p>{error}</p>
      </div>
    );
  }

  return (
    <div style={{ display: "flex", justifyContent: "center", alignItems: "center", height: "100vh" }}>
      <p>Authenticating, please wait...</p>
    </div>
  );
};

export default AuthCallback;

