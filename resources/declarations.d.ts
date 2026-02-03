declare module "*?raw"
{
    const content: string;
    export default content;
}

declare module "*?url"
{
    const url: string;
    export default url;
}

declare module "*.svg"
{
    const content: string;
    export default content;
}

interface Window {
    picowind: {
        // The version of Picowind.
        _version: string;

        _wp_version: string;

        assets: {
            url: string;
        };

        user_data: {
            data_dir: {
                url: string;
            };

            cache_dir: string[];
        };

        _wpnonce: string;

        rest_api: {
            // The base URL of the Picowind REST API endpoint.
            url: string;

            // The nonce for authenticating requests to the Picowind REST API.
            nonce: string;
        };

        site_meta: {
            name: string;

            site_url: string;

            // Vue Router base URL.
            web_history: string;
        };

        current_user: {
            name: string;

            avatar: string;

            role: string;
        };

        is_debug: boolean;
    };
}