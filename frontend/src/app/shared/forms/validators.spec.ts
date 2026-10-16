import { FormControl, FormGroup } from '@angular/forms';

import { matchFields, pakistanMobile } from './validators';

describe('pakistanMobile', () => {
  it('accepts 03001234567', () => {
    expect(pakistanMobile(new FormControl('03001234567'))).toBeNull();
  });

  it('rejects a short or wrong number', () => {
    expect(pakistanMobile(new FormControl('12345'))).toEqual({ pakistanMobile: true });
  });

  it('leaves empty values to the required validator', () => {
    expect(pakistanMobile(new FormControl(''))).toBeNull();
  });
});

describe('matchFields', () => {
  it('flags passwords that do not match', () => {
    const group = new FormGroup(
      { password: new FormControl('secret123'), password_confirmation: new FormControl('other') },
      { validators: matchFields('password', 'password_confirmation') },
    );
    expect(group.hasError('fieldsMismatch')).toBe(true);
  });
});
