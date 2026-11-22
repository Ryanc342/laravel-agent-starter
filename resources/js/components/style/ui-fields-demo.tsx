import { Specimen } from '@/components/style/style-section';
import CheckboxField from '@/components/shared/checkbox-field';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Toggle } from '@/components/ui/toggle';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

/**
 * What the user ticks or switches: Checkbox, Toggle, ToggleGroup, InputOTP.
 * The boxed fields (text, select, date) are under Shared blocks.
 */
export default function UiFieldsDemo() {
    return (
        <div className="grid gap-8">
            <Specimen label="CheckboxField: yes or no, in three states">
                <CheckboxField label="Unchecked" />
                <CheckboxField label="Checked" defaultChecked />
                <CheckboxField label="Disabled" disabled />
            </Specimen>

            <Specimen label="ToggleGroup: two to seven modes. Toggle: one switch on its own">
                <ToggleGroup
                    type="single"
                    variant="outline"
                    defaultValue="list"
                >
                    <ToggleGroupItem value="list" aria-label="List">
                        List
                    </ToggleGroupItem>
                    <ToggleGroupItem value="cards" aria-label="Cards">
                        Cards
                    </ToggleGroupItem>
                    <ToggleGroupItem value="table" aria-label="Table">
                        Table
                    </ToggleGroupItem>
                </ToggleGroup>
                <Toggle variant="outline" aria-label="Bold">
                    Bold
                </Toggle>
            </Specimen>

            <Specimen label="InputOTP: a one-time code">
                <InputOTP maxLength={6} aria-label="Code">
                    <InputOTPGroup>
                        {[0, 1, 2, 3, 4, 5].map((index) => (
                            <InputOTPSlot key={index} index={index} />
                        ))}
                    </InputOTPGroup>
                </InputOTP>
            </Specimen>
        </div>
    );
}
