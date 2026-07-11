import { Star } from 'lucide-react';

type Props = {
  rating: number;
};

export function RatingStars({ rating }: Props) {
  return (
    <div className="flex gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <Star key={star} className={`h-4 w-4 ${star <= rating ? 'fill-[#e5a11a] text-[#e5a11a]' : 'text-[#d9dece]'}`} />
      ))}
    </div>
  );
}
