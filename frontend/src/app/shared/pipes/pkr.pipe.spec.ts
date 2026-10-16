import { PkrPipe } from './pkr.pipe';

describe('PkrPipe', () => {
  const pipe = new PkrPipe();

  it('formats rupees with thousands separators', () => {
    expect(pipe.transform(1250)).toBe('Rs 1,250');
  });

  it('shows a dash for empty values', () => {
    expect(pipe.transform(null)).toBe('—');
    expect(pipe.transform(undefined)).toBe('—');
  });
});
