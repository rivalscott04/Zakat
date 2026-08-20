import { Alert } from "reactstrap";
import { getUserErrorMessage } from "../api/userMessage";

type Props = { error: unknown; onClose?: () => void };

const ErrorAlert = ({ error, onClose }: Props) => (
  <Alert color="danger" toggle={onClose} role="alert">
    {getUserErrorMessage(error)}
  </Alert>
);

export default ErrorAlert;
