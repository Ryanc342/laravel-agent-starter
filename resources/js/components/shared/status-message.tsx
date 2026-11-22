/**
 * A one-line confirmation the server sends back with a page: "A new
 * verification link has been sent", "Your password has been reset". Use it
 * for a status that belongs to the page the user lands on, centred, in the
 * success colour. Nothing is shown without a message.
 */
export default function StatusMessage({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p
            role="status"
            className="text-center text-sm font-medium text-success"
        >
            {message}
        </p>
    );
}
