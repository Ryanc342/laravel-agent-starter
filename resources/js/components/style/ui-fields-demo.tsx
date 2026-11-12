import { Specimen } from '@/components/style/style-section';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Toggle } from '@/components/ui/toggle';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

/** What the user types or chooses: Input, Select, Checkbox, Toggle, ToggleGroup, InputOTP. */
export default function UiFieldsDemo() {
    return (
        <div className="grid gap-8">
            <Specimen label="Input: empty, filled, disabled, and with an error">
                <Input className="w-56" placeholder="Placeholder" />
                <Input className="w-56" defaultValue="A value" />
                <Input className="w-56" defaultValue="Disabled" disabled />
                <Input className="w-56" defaultValue="Not valid" aria-invalid />
            </Specimen>

            <Specimen label="Select: a list of options">
                <Select defaultValue="open">
                    <SelectTrigger className="w-56">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="open">Open</SelectItem>
                        <SelectItem value="done">Done</SelectItem>
                        <SelectItem value="overdue">Overdue</SelectItem>
                    </SelectContent>
                </Select>
                <Select disabled>
                    <SelectTrigger className="w-56">
                        <SelectValue placeholder="Disabled" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="open">Open</SelectItem>
                    </SelectContent>
                </Select>
            </Specimen>

            <Specimen label="Checkbox: yes or no, in three states">
                <div className="flex items-center gap-2">
                    <Checkbox id="style-unchecked" />
                    <Label htmlFor="style-unchecked">Unchecked</Label>
                </div>
                <div className="flex items-center gap-2">
                    <Checkbox id="style-checked" defaultChecked />
                    <Label htmlFor="style-checked">Checked</Label>
                </div>
                <div className="flex items-center gap-2">
                    <Checkbox id="style-disabled" disabled />
                    <Label htmlFor="style-disabled">Disabled</Label>
                </div>
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
                <InputOTP maxLength={6}>
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
