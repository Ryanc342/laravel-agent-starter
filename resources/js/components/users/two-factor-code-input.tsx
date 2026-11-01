import { REGEXP_ONLY_DIGITS } from 'input-otp';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';

type TwoFactorCodeInputProps = {
    id?: string;
    value?: string;
    onChange: (value: string) => void;
    disabled: boolean;
};

/**
 * The boxes for a two-factor code, shared by the login challenge and the setup dialog.
 */
export default function TwoFactorCodeInput({
    id,
    value,
    onChange,
    disabled,
}: TwoFactorCodeInputProps) {
    return (
        <InputOTP
            id={id}
            name="code"
            maxLength={OTP_MAX_LENGTH}
            value={value}
            onChange={onChange}
            disabled={disabled}
            pattern={REGEXP_ONLY_DIGITS}
            autoFocus
        >
            <InputOTPGroup>
                {Array.from({ length: OTP_MAX_LENGTH }, (_, index) => (
                    <InputOTPSlot key={index} index={index} />
                ))}
            </InputOTPGroup>
        </InputOTP>
    );
}
